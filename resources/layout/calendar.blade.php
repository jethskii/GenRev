<div class="calendar-btn" onclick="toggleCalendar()">
    <div class="calendar-icon"></div>
</div>

<div class="calendar-overlay" id="calendarOverlay">
    <div class="calendar-modal">
        <div class="calendar-header">
            <span>📅 Select a Date</span>
            <button class="calendar-close" onclick="toggleCalendar()">×</button>
        </div>
        <div class="calendar-body">
            <iframe src="https://calendar.google.com/calendar/embed?src=en.philippines%23holiday%40group.v.calendar.google.com&ctz=Asia%2FManila"
                    style="border:0" width="100%" height="400" frameborder="0" scrolling="no"></iframe>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleCalendar() {
        document.getElementById('calendarOverlay').classList.toggle('active');
    }
</script>
@endpush
