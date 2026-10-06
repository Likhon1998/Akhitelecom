<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcodes — {{ $products->count() }} product(s)</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 20px;
            font-family: Inter, Segoe UI, Arial, sans-serif;
            background: #f4f6fb;
            color: #0f172a;
        }
        .toolbar {
            max-width: 980px;
            margin: 0 auto 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
        }
        .toolbar h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
        }
        .toolbar p {
            margin: 2px 0 0;
            font-size: 12px;
            color: #64748b;
        }
        .actions { display: flex; gap: 8px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .grid {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
        }
        .label {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 14px 12px;
            text-align: center;
            page-break-inside: avoid;
        }
        .label svg.barcode {
            display: block;
            max-width: 100%;
            height: auto;
            margin: 0 auto;
        }
        .label h3 {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.3;
        }
        .label .price {
            margin: 8px 0 0;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }
        .label .meta {
            margin: 2px 0 0;
            font-size: 10px;
            color: #94a3b8;
            font-family: ui-monospace, monospace;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .grid { max-width: none; gap: 10px; padding: 8px; }
            .label {
                border: 1px solid #000;
                border-radius: 0;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Barcode labels ready</h1>
            <p>{{ $products->count() }} product(s) · {{ $copies }} cop{{ $copies === 1 ? 'y' : 'ies' }} each · {{ $labelCount }} label(s) total · phones get one label per phone with its own IMEI</p>
        </div>
        <div class="actions">
            <a class="btn btn-back" href="{{ route('products.barcodes') }}">Back to list</a>
            <button class="btn btn-print" onclick="window.print()">Print now</button>
        </div>
    </div>

    <div class="grid">
        @foreach($products as $product)
            @if($product->requires_imei && $product->availableImeis->isNotEmpty())
                @foreach($product->availableImeis as $phone)
                    @for($i = 0; $i < $copies; $i++)
                        <div class="label">
                            <h3>{{ $product->name }}</h3>
                            <svg class="barcode"
                                 jsbarcode-format="CODE128"
                                 jsbarcode-value="{{ $phone->imei }}"
                                 jsbarcode-height="48"
                                 jsbarcode-displayValue="true"
                                 jsbarcode-fontSize="12"
                                 jsbarcode-margin="4"></svg>
                            <p class="price">Tk {{ number_format($product->selling_price, 2) }}</p>
                            <p class="meta">IMEI {{ $phone->imei }}@if($phone->imei_2) · IMEI 2 {{ $phone->imei_2 }}@endif</p>
                        </div>
                    @endfor
                @endforeach
            @else
                @for($i = 0; $i < $copies; $i++)
                    <div class="label">
                        <h3>{{ $product->name }}</h3>
                        <svg class="barcode"
                             jsbarcode-format="CODE128"
                             jsbarcode-value="{{ $product->barcode }}"
                             jsbarcode-height="48"
                             jsbarcode-displayValue="true"
                             jsbarcode-fontSize="12"
                             jsbarcode-margin="4"></svg>
                        <p class="price">Tk {{ number_format($product->selling_price, 2) }}</p>
                        <p class="meta">{{ $product->barcode }}</p>
                        @if($product->requires_imei)
                            <p class="meta" style="color:#b45309">No phones with IMEI in stock</p>
                        @endif
                    </div>
                @endfor
            @endif
        @endforeach
    </div>

    <script>
        JsBarcode('.barcode').init();
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 350);
        });
    </script>
</body>
</html>
