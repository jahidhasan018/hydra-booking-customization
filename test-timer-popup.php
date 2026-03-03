<?php
/**
 * Test Timer and Bengali Popup Notification
 * 
 * This creates a simple test page to verify the meeting timer and Bengali popup functionality
 * URL: /wp-content/plugins/hydra-booking-customization/test-timer-popup.php
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    // Load WordPress if accessed directly
    require_once( '../../../wp-load.php' );
}

// Check if user has admin capabilities
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have sufficient permissions to access this page.' );
}

// Create a mock booking object for testing
$mock_booking = (object) [
    'id' => 999,
    'duration' => 2, // 2 minutes for quick testing
    'meeting_dates' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_time' => date('H:i:s', strtotime('+2 minutes')),
    'status' => 'confirmed'
];

$participant_name = 'Test User';
$meeting_title = 'Test Meeting - Timer & Bengali Popup';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Meeting Timer & Bengali Popup</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f0f0f0;
        }
        
        .meeting-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
        }
        
        .meeting-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .meeting-title {
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .meeting-timer {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
        
        .timer-elapsed {
            font-family: 'Courier New', monospace;
            font-size: 1.1em;
            font-weight: bold;
        }
        
        .timer-duration {
            font-family: 'Noto Sans Bengali', Arial, sans-serif;
            font-size: 0.8em;
            opacity: 0.9;
        }
        
        .meeting-timer.overtime {
            background: #ff4757;
            border-color: #ff3742;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .participant-name {
            font-size: 1em;
        }
        
        .meeting-notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .meeting-notification {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .meeting-notification h2 {
            font-family: 'Noto Sans Bengali', Arial, sans-serif;
            color: #e74c3c;
            font-size: 2em;
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        
        .meeting-notification p {
            font-family: 'Noto Sans Bengali', Arial, sans-serif;
            color: #2c3e50;
            font-size: 1.3em;
            margin: 0 0 25px 0;
            line-height: 1.5;
        }
        
        .meeting-notification button {
            font-family: 'Noto Sans Bengali', Arial, sans-serif;
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1em;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .meeting-notification button:hover {
            background: #2980b9;
        }
        
        .test-content {
            padding: 30px;
        }
        
        .test-info {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .test-controls {
            margin: 20px 0;
        }
        
        .test-controls button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .test-controls button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="meeting-container">
        <div class="meeting-header">
            <div class="meeting-title"><?php echo esc_html( $meeting_title ); ?></div>
            <div class="user-info">
                <div class="meeting-timer" id="meeting-timer">
                    <div class="timer-elapsed">00:00</div>
                    <div class="timer-duration">সময়কাল <?php echo $mock_booking->duration; ?> min</div>
                </div>
                <div class="participant-name"><?php echo esc_html( $participant_name ); ?></div>
            </div>
        </div>
        
        <div class="test-content">
            <div class="test-info">
                <h3>🧪 Timer & Bengali Popup Test</h3>
                <p><strong>Meeting Duration:</strong> <?php echo $mock_booking->duration; ?> minutes (for quick testing)</p>
                <p><strong>Expected Behavior:</strong></p>
                <ul>
                    <li>Timer starts at 00:00 and counts up</li>
                    <li>After <?php echo $mock_booking->duration; ?> minutes, timer turns red and pulses</li>
                    <li>Bengali popup notification appears: "মিটিংয়ের সময় শেষ হয়েছে"</li>
                    <li>Popup auto-closes after 10 seconds or can be manually closed</li>
                </ul>
            </div>
            
            <div class="test-controls">
                <button onclick="testPopupNow()">🧪 Test Bengali Popup Now</button>
                <button onclick="resetTimer()">🔄 Reset Timer</button>
                <button onclick="simulateOvertime()">⏰ Simulate Overtime</button>
            </div>
            
            <div id="timer-status"></div>
        </div>
    </div>

    <script>
        // Meeting timer and notification system
        const bookingId = '<?php echo esc_js( $mock_booking->id ?? 'test' ); ?>';
        const storageKey = 'meeting_start_time_' + bookingId;
        let meetingStartTime;
        let meetingDuration = <?php echo intval( $mock_booking->duration ?? 30 ); ?> * 60 * 1000; // Convert minutes to milliseconds
        let timerInterval;
        let notificationShown = false;
        
        // Initialize or retrieve meeting start time
        function initializeMeetingTime() {
            const storedStartTime = localStorage.getItem(storageKey);
            if (storedStartTime) {
                meetingStartTime = new Date(parseInt(storedStartTime));
            } else {
                meetingStartTime = new Date();
                localStorage.setItem(storageKey, meetingStartTime.getTime().toString());
            }
        }
        
        function updateTimer() {
            const now = new Date();
            const elapsed = now - meetingStartTime;
            const minutes = Math.floor(elapsed / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            
            const timerElement = document.getElementById('meeting-timer');
            const elapsedElement = timerElement.querySelector('.timer-elapsed');
            const formattedTime = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            elapsedElement.textContent = formattedTime;
            
            // Update status
            const statusElement = document.getElementById('timer-status');
            statusElement.innerHTML = `<p><strong>Elapsed:</strong> ${formattedTime} | <strong>Duration:</strong> ${Math.floor(meetingDuration/60000)} minutes | <strong>Remaining:</strong> ${Math.max(0, Math.floor((meetingDuration - elapsed)/60000))} minutes ${Math.max(0, Math.floor(((meetingDuration - elapsed)%60000)/1000))} seconds</p>`;
            
            // Check if meeting duration has elapsed
            if (elapsed >= meetingDuration && !notificationShown) {
                timerElement.classList.add('overtime');
                showMeetingEndNotification();
                notificationShown = true;
            }
        }
        
        function showMeetingEndNotification() {
            const overlay = document.createElement('div');
            overlay.className = 'meeting-notification-overlay';
            overlay.innerHTML = `
                <div class="meeting-notification">
                    <h2>মিটিংয়ের সময় শেষ</h2>
                    <p>মিটিংয়ের সময় শেষ হয়েছে</p>
                    <button onclick="closeMeetingNotification()">ঠিক আছে</button>
                </div>
            `;
            document.body.appendChild(overlay);
            
            // Auto-close after 10 seconds if not manually closed
            setTimeout(() => {
                if (document.body.contains(overlay)) {
                    overlay.remove();
                }
            }, 10000);
        }
        
        function closeMeetingNotification() {
            const overlay = document.querySelector('.meeting-notification-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
        
        // Make function globally accessible
        window.closeMeetingNotification = closeMeetingNotification;
        
        // Start timer when page loads
        function startMeetingTimer() {
            initializeMeetingTime();
            timerInterval = setInterval(updateTimer, 1000);
            updateTimer(); // Initial call
        }
        
        // Reset timer for new meeting session
        function resetMeetingTimer() {
            localStorage.removeItem(storageKey);
            initializeMeetingTime();
            notificationShown = false;
            const timerElement = document.getElementById('meeting-timer');
            if (timerElement) {
                timerElement.classList.remove('overtime');
            }
        }
        
        // Test functions
        function testPopupNow() {
            showMeetingEndNotification();
        }
        
        function resetTimer() {
            resetMeetingTimer();
        }
        
        function simulateOvertime() {
            meetingStartTime = new Date(Date.now() - meetingDuration - 5000); // 5 seconds overtime
            notificationShown = false;
            updateTimer();
        }
        
        // Start the meeting timer when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startMeetingTimer();
        });
    </script>
</body>
</html>