{{-- Compact CTA + sheet: never replaces the home banner. Opens only on user click. --}}
<div x-data="appointmentBooking()"
     x-on:open-appointment.window="open()">
    <button type="button"
            @click="open()"
            class="tn-appt-fab"
            aria-label="Book an appointment">
        <svg class="tn-appt-fab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="tn-appt-fab-label">Book an Appointment</span>
    </button>

    <div x-show="openSheet"
         x-cloak
         x-transition.opacity
         class="tn-appt-overlay"
         @keydown.escape.window="close()"
         @click.self="close()">
        <div class="tn-appt-sheet"
             x-show="openSheet"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="translate-y-full sm:opacity-0 sm:scale-95"
             x-transition:enter-end="translate-y-0 sm:opacity-100 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 sm:opacity-100 sm:scale-100"
             x-transition:leave-end="translate-y-full sm:opacity-0 sm:scale-95"
             @click.stop
             role="dialog"
             aria-modal="true"
             aria-labelledby="tn-appt-title">
            <div class="tn-appt-sheet-head">
                <div>
                    <p class="tn-appt-kicker">Visit / service</p>
                    <h3 id="tn-appt-title" class="tn-appt-title">Book an Appointment</h3>
                    <p class="tn-appt-sub">Home banners stay visible — send a request and we’ll confirm by phone.</p>
                </div>
                <button type="button" class="tn-appt-close" @click="close()" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form class="tn-appt-form" @submit.prevent="submit()">
                <input type="hidden" name="appointment" value="1">
                <div class="tn-appt-grid">
                    <label class="tn-appt-field">
                        <span>Your name</span>
                        <input type="text" x-model="form.name" required maxlength="120" placeholder="Full name" autocomplete="name">
                    </label>
                    <label class="tn-appt-field">
                        <span>Mobile number</span>
                        <input type="tel" x-model="form.phone" required maxlength="32" placeholder="01XXXXXXXXX" autocomplete="tel">
                    </label>
                    <label class="tn-appt-field">
                        <span>Service</span>
                        <select x-model="form.service" required>
                            <option value="">Select service</option>
                            <option>Phone repair / diagnostics</option>
                            <option>Screen / battery replacement</option>
                            <option>Software / unlock help</option>
                            <option>In-store product consultation</option>
                            <option>Other</option>
                        </select>
                    </label>
                    <label class="tn-appt-field">
                        <span>Preferred date</span>
                        <input type="date" x-model="form.preferred_date" :min="minDate" required>
                    </label>
                    <label class="tn-appt-field">
                        <span>Preferred time</span>
                        <select x-model="form.preferred_time" required>
                            <option value="">Select time</option>
                            <option>Morning (10am–1pm)</option>
                            <option>Afternoon (1pm–5pm)</option>
                            <option>Evening (5pm–9pm)</option>
                        </select>
                    </label>
                    <label class="tn-appt-field tn-appt-field--full">
                        <span>Email <em>(optional)</em></span>
                        <input type="email" x-model="form.email" maxlength="255" placeholder="you@email.com" autocomplete="email">
                    </label>
                    <label class="tn-appt-field tn-appt-field--full">
                        <span>Notes</span>
                        <textarea x-model="form.message" rows="3" required maxlength="5000" placeholder="Device model, issue, or anything we should know…"></textarea>
                    </label>
                </div>

                <p x-show="error" x-text="error" class="tn-appt-error"></p>
                <p x-show="success" x-text="success" class="tn-appt-success"></p>

                <div class="tn-appt-actions">
                    <button type="button" class="tn-appt-btn tn-appt-btn--ghost" @click="close()" :disabled="sending">Cancel</button>
                    <button type="submit" class="tn-appt-btn tn-appt-btn--primary" :disabled="sending">
                        <span x-text="sending ? 'Sending…' : 'Request appointment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.appointmentBooking = window.appointmentBooking || function appointmentBooking() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');

    return {
        openSheet: false,
        sending: false,
        error: '',
        success: '',
        minDate: `${yyyy}-${mm}-${dd}`,
        form: {
            name: '',
            phone: '',
            email: '',
            service: '',
            preferred_date: '',
            preferred_time: '',
            message: '',
        },
        open() {
            this.error = '';
            this.success = '';
            this.openSheet = true;
            document.documentElement.classList.add('tn-appt-lock');
        },
        close() {
            if (this.sending) return;
            this.openSheet = false;
            document.documentElement.classList.remove('tn-appt-lock');
        },
        async submit() {
            this.error = '';
            this.success = '';
            this.sending = true;
            try {
                const payload = {
                    appointment: true,
                    name: this.form.name,
                    phone: this.form.phone,
                    email: this.form.email || null,
                    subject: 'Appointment: ' + (this.form.service || 'Store visit'),
                    service: this.form.service,
                    preferred_date: this.form.preferred_date,
                    preferred_time: this.form.preferred_time,
                    message: this.form.message,
                };
                const res = await window.fetchJsonWithCsrf(@json(route('website.contact.submit')), {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const first = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                    throw new Error(first || data?.message || 'Could not send request. Please try again.');
                }
                this.success = data.message || 'Appointment request sent.';
                this.form.message = '';
                this.form.service = '';
                this.form.preferred_date = '';
                this.form.preferred_time = '';
                setTimeout(() => this.close(), 1600);
            } catch (e) {
                this.error = e?.message || 'Could not send request.';
            } finally {
                this.sending = false;
            }
        },
        init() {
            if (window.location.hash === '#appointment') {
                this.$nextTick(() => this.open());
            }
        },
    };
};
</script>
