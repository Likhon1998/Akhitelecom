<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class AiChatController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $shopId = (int) $user->shop_id;
        if ($shopId < 1) {
            return response()->json(['reply' => 'Your account is not linked to a shop.']);
        }

        $userMessage = $request->message;
        $apiKey = env('GEMINI_API_KEY');

        if (! $apiKey) {
            return response()->json(['reply' => 'API Key is missing in your .env file.']);
        }

        $schema = $this->getDynamicDatabaseSchema();

        $routerPrompt = "You are the core intelligence engine for 'Nexa POS'.
        User Message: '{$userMessage}'

        Here is the dynamic database schema of the system:
        $schema

        INSTRUCTIONS:
        1. CONVERSATION: If the user is just saying hello or asking a non-data question, reply starting with EXACTLY the word 'CHAT:' followed by your friendly response.

        2. DATABASE QUERY: If the user asks for data (sales, staff, products, stock, exchanges, customers), write a raw SQL SELECT query.

        3. STRICT RULES FOR SQL (CRITICAL):
           - RETURN ONLY the raw SQL string. No markdown, no backticks, no talk.
           - TENANT ISOLATION (CRITICAL): Every query MUST include shop_id = {$shopId} (or shops.id = {$shopId} for the shops table).
           - REVENUE LOGIC: When calculating total sales/revenue from the 'orders' table, YOU MUST STRICTLY ADD: AND status = 'completed' AND (is_exchange_receipt = false OR is_exchange_receipt = 0). Do NOT sum refunded, cancelled, or exchange orders.
           - TIMEZONE: Prefer filtering with created_at in UTC. Bangladesh is UTC+6.
           - LIMIT 15 to avoid server overload.
           - SELECT only. Never modify data.";

        $aiResponse = $this->callGemini($apiKey, $routerPrompt);
        $cleanedResponse = trim(preg_replace('/^```sql|```|sql$/m', '', $aiResponse));

        if (str_starts_with(strtoupper($cleanedResponse), 'CHAT:')) {
            $chatReply = $this->plainTextReply(trim(substr($cleanedResponse, 5)));

            return response()->json(['reply' => $chatReply]);
        }

        $sqlQuery = stristr($cleanedResponse, 'SELECT');
        $sqlQuery = $sqlQuery ? trim($sqlQuery) : '';

        if ($sqlQuery === '') {
            return response()->json(['reply' => 'I can only answer shop data questions right now. Try asking about sales, stock, or products.']);
        }

        try {
            $sqlQuery = $this->assertSafeSelect($sqlQuery, $shopId);
        } catch (InvalidArgumentException $e) {
            Log::warning('AI SQL rejected: '.$e->getMessage().' | '.$sqlQuery);

            return response()->json(['reply' => 'Security Error: That query is not allowed.']);
        }

        Log::info('AI SQL Query Executed: '.$sqlQuery);

        try {
            $dbResult = DB::select($sqlQuery);
        } catch (\Exception $e) {
            Log::error('AI SQL Execution Failed: '.$e->getMessage().' | Query: '.$sqlQuery);

            return response()->json(['reply' => 'Could not run that data query. Please try again with a simpler question.']);
        }

        $dataJson = json_encode($dbResult);

        $humanPrompt = "You are 'Nexa AI', a friendly assistant for Nexa POS.
        User asked: '{$userMessage}'
        Database returned: {$dataJson}

        Task: Summarize this data clearly for a shop owner.

        FORMATTING RULES (CRITICAL):
        1. Plain text only. No HTML tags. No markdown code fences.
        2. You may use simple line breaks and bullet lines starting with '- '.
        3. Structure: short opening, bullet list of facts, short closing.
        4. LANGUAGE: Reply in Banglish if they asked in Banglish. If English, reply in English.
        5. EMPTY DATA: If the data is empty `[]`, say 'No records found for that question.'
        6. NEVER show SQL code.";

        $finalReply = $this->plainTextReply($this->callGemini($apiKey, $humanPrompt));

        return response()->json(['reply' => $finalReply ?: 'Could not summarize the data.']);
    }

    /**
     * Allow only single SELECT statements scoped to the current shop.
     */
    private function assertSafeSelect(string $sql, int $shopId): string
    {
        $sql = trim($sql);
        $sql = rtrim($sql, "; \t\n\r");

        if (str_contains($sql, ';')) {
            throw new InvalidArgumentException('Multiple statements are not allowed.');
        }

        $upper = strtoupper(preg_replace('/\s+/', ' ', $sql) ?? $sql);

        if (! str_starts_with($upper, 'SELECT ')) {
            throw new InvalidArgumentException('Only SELECT is allowed.');
        }

        $forbidden = [
            ' INSERT ', ' UPDATE ', ' DELETE ', ' DROP ', ' ALTER ', ' TRUNCATE ',
            ' CREATE ', ' REPLACE ', ' GRANT ', ' REVOKE ', ' CALL ', ' EXEC ',
            ' INTO OUTFILE', ' INTO DUMPFILE', ' LOAD_FILE', ' INFORMATION_SCHEMA',
            ' PG_CATALOG', ' SLEEP(', ' BENCHMARK(', ' COPY ', ' ATTACH ',
        ];

        $padded = ' '.$upper.' ';
        foreach ($forbidden as $token) {
            if (str_contains($padded, $token)) {
                throw new InvalidArgumentException('Forbidden SQL token: '.trim($token));
            }
        }

        $shopFilter = (string) $shopId;
        $hasShopId = (bool) preg_match('/\bshop_id\s*=\s*'.$shopFilter.'\b/i', $sql);
        $hasShopRow = (bool) preg_match('/\b(?:shops\.)?id\s*=\s*'.$shopFilter.'\b/i', $sql);

        if (! $hasShopId && ! $hasShopRow) {
            throw new InvalidArgumentException('Missing shop_id tenant filter.');
        }

        if (! preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql .= ' LIMIT 15';
        } else {
            $sql = preg_replace_callback('/\bLIMIT\s+(\d+)/i', function ($m) {
                $n = min(50, max(1, (int) $m[1]));

                return 'LIMIT '.$n;
            }, $sql, 1) ?? $sql;
        }

        return $sql;
    }

    private function plainTextReply(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function getDynamicDatabaseSchema(): string
    {
        $ignoredTables = [
            'migrations', 'password_reset_tokens', 'personal_access_tokens',
            'failed_jobs', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches',
            'permissions', 'roles', 'model_has_permissions', 'model_has_roles', 'role_has_permissions',
            'users',
        ];

        $schemaString = '';
        $tables = Schema::getTableListing();

        foreach ($tables as $table) {
            if (in_array($table, $ignoredTables, true)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $columns = array_values(array_diff($columns, ['password', 'remember_token']));
            $schemaString .= "Table: {$table} (".implode(', ', $columns).")\n";
        }

        return $schemaString;
    }

    private function callGemini($apiKey, $prompt, $retries = 1)
    {
        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key='.$apiKey;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();

                return $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            }

            if ($response->status() == 503 && $retries > 0) {
                sleep(1);

                return $this->callGemini($apiKey, $prompt, $retries - 1);
            }

            Log::error('Gemini API Error: '.$response->status().' - '.$response->body());

            return '';
        } catch (\Exception $e) {
            Log::error('Gemini Connection Error: '.$e->getMessage());

            return '';
        }
    }
}
