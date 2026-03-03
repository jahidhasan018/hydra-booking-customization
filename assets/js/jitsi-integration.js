/**
 * Jitsi Integration JavaScript for Attendee Dashboard
 * 
 * Handles secure meeting join functionality with proper error handling
 * and user experience enhancements.
 * 
 * @package HydraBookingCustomization
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main Jitsi Integration object
     */
    const HBCJitsi = {
        
        /**
         * Initialize the integration
         */
        init: function() {
            this.bindEvents();
            this.initMeetingStatusUpdater();
            this.validateConfiguration();
        },

        /**
         * Validate required configuration
         */
        validateConfiguration: function() {
            if (typeof hbc_jitsi === 'undefined') {
                console.error('HBC Jitsi: Configuration object not found');
                return false;
            }

            const required = ['ajax_url', 'nonce', 'strings'];
            for (let prop of required) {
                if (!hbc_jitsi.hasOwnProperty(prop)) {
                    console.error('HBC Jitsi: Missing required configuration:', prop);
                    return false;
                }
            }
            return true;
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).on('click', '.hbc-join-meeting-btn', this.handleJoinMeeting.bind(this));
            $(document).on('click', '.hbc-modal-close', this.closeModal);
            $(document).on('click', '.hbc-modal', function(e) {
                if (e.target === this) {
                    HBCJitsi.closeModal.call(this);
                }
            });
        },

        /**
         * Handle join meeting button clicks
         */
        handleJoinMeeting: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const bookingId = this.sanitizeInput($button.data('booking-id'));
            
            // Validate booking ID
            if (!bookingId || isNaN(bookingId) || bookingId <= 0) {
                this.showError(hbc_jitsi.strings.invalid_booking || 'Invalid booking ID');
                return;
            }
            
            // Check if button is already processing
            if ($button.prop('disabled')) {
                return;
            }
            
            this.setButtonLoading($button, true);
            
            // Make secure AJAX request
            this.joinMeeting(bookingId, $button);
        },

        /**
         * Make AJAX request to join meeting
         */
        joinMeeting: function(bookingId, $button) {
            const self = this;
            
            $.ajax({
                url: hbc_jitsi.ajax_url,
                type: 'POST',
                data: {
                    action: 'hbc_join_jitsi_meeting',
                    booking_id: parseInt(bookingId, 10),
                    nonce: hbc_jitsi.nonce
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    self.handleJoinResponse(response, $button);
                },
                error: function(xhr, status, error) {
                    self.handleJoinError(xhr, status, error, $button);
                },
                complete: function() {
                    self.setButtonLoading($button, false);
                }
            });
        },

        /**
         * Handle successful join response
         */
        handleJoinResponse: function(response, $button) {
            console.log('HBC Jitsi: Handling join response:', response);
            
            if (response.success && response.data) {
                console.log('HBC Jitsi: Raw meeting URL from response:', response.data.meeting_url);
                
                const meetingUrl = this.sanitizeUrl(response.data.meeting_url);
                const roomName = this.sanitizeInput(response.data.room_name);
                
                console.log('HBC Jitsi: Sanitized meeting URL:', meetingUrl);
                console.log('HBC Jitsi: Room name:', roomName);
                
                if (!meetingUrl) {
                    console.error('HBC Jitsi: Meeting URL failed sanitization');
                    this.showError(hbc_jitsi.strings.invalid_url || 'Meeting link not available. Please contact support.');
                    return;
                }
                
                // Attempt to open meeting in new window
                const meetingWindow = this.openMeetingWindow(meetingUrl);
                
                if (meetingWindow) {
                    this.updateButtonSuccess($button);
                    this.logMeetingJoin(response.data.booking_id, response.data.role);
                } else {
                    // Popup blocked - show modal with direct link
                    this.showMeetingLinkModal(meetingUrl, roomName);
                }
            } else {
                console.error('HBC Jitsi: Response indicates failure:', response);
                const errorMessage = response.data && response.data.message 
                    ? response.data.message 
                    : (hbc_jitsi.strings.error_joining || 'Failed to join meeting');
                this.showError(errorMessage);
            }
        },

        /**
          * Handle join request errors
          */
         handleJoinError: function(xhr, status, error, $button) {
             let errorMessage = hbc_jitsi.strings.error_joining || 'Failed to join meeting';
             
             if (status === 'timeout') {
                 errorMessage = hbc_jitsi.strings.timeout_error || 'Request timed out. Please try again.';
             } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                 errorMessage = xhr.responseJSON.data.message;
             }
             
             console.error('HBC Jitsi: Join meeting error:', {
                 status: status,
                 error: error,
                 response: xhr.responseText
             });
             
             this.showError(errorMessage);
         },

         /**
          * Open meeting window with security checks
          */
         openMeetingWindow: function(url) {
             try {
                 const meetingWindow = window.open(
                     url,
                     '_blank',
                     'width=1200,height=800,scrollbars=yes,resizable=yes,noopener=yes,noreferrer=yes'
                 );
                 
                 if (meetingWindow) {
                     meetingWindow.focus();
                 }
                 
                 return meetingWindow;
             } catch (error) {
                 console.error('HBC Jitsi: Failed to open meeting window:', error);
                 return null;
             }
         },

         /**
          * Set button loading state
          */
         setButtonLoading: function($button, isLoading) {
             if (isLoading) {
                 $button.prop('disabled', true);
                 $button.data('original-text', $button.html());
                 $button.html('<i class="fas fa-spinner fa-spin"></i> ' + 
                     (hbc_jitsi.strings.joining_meeting || 'Joining...'));
             } else {
                 $button.prop('disabled', false);
                 if (!$button.hasClass('hbc-btn-joined')) {
                     const originalText = $button.data('original-text');
                     if (originalText) {
                         $button.html(originalText);
                     }
                 }
             }
         },

         /**
          * Update button to success state
          */
         updateButtonSuccess: function($button) {
             $button.removeClass('hbc-btn-ready').addClass('hbc-btn-joined');
             $button.html('<i class="fas fa-external-link-alt"></i> ' + 
                 (hbc_jitsi.strings.meeting_opened || 'Meeting Opened'));
         },

         /**
         * Show error message
         */
        showError: function(message) {
            // Use toast notifications if available, fallback to alert
            if (window.HBC && window.HBC.toast) {
                window.HBC.toast.error(message);
            } else if (window.ToastNotifications) {
                window.ToastNotifications.error(message);
            } else if (typeof this.showNotification === 'function') {
                this.showNotification(message, 'error');
            } else {
                alert(message);
            }
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            // Use toast notifications if available
            if (window.HBC && window.HBC.toast) {
                window.HBC.toast.success(message);
            } else if (window.ToastNotifications) {
                window.ToastNotifications.success(message);
            } else if (typeof this.showNotification === 'function') {
                this.showNotification(message, 'success');
            }
        },

        /**
         * Show info message
         */
        showInfo: function(message) {
            // Use toast notifications if available
            if (window.HBC && window.HBC.toast) {
                window.HBC.toast.info(message);
            } else if (window.ToastNotifications) {
                window.ToastNotifications.info(message);
            } else if (typeof this.showNotification === 'function') {
                this.showNotification(message, 'info');
            }
        },

         /**
          * Sanitize input data
          */
         sanitizeInput: function(input) {
             if (typeof input === 'string') {
                 return input.replace(/[<>'"]/g, '');
             }
             return input;
         },

         /**
          * Sanitize URL
          */
         sanitizeUrl: function(url) {
             if (typeof url !== 'string' || !url) {
                 return null;
             }
             
             // Basic URL validation - more permissive for local development
             try {
                 // First try standard URL validation
                 const urlObj = new URL(url);
                 // Allow both https and http for local development
                 if (urlObj.protocol !== 'https:' && urlObj.protocol !== 'http:') {
                     console.warn('HBC Jitsi: Invalid protocol detected:', url);
                     return null;
                 }
                 return url;
             } catch (error) {
                 // Fallback validation for local domains that might not parse correctly
                 if (url.match(/^https?:\/\/.+/)) {
                     console.log('HBC Jitsi: Using fallback URL validation for:', url);
                     return url;
                 }
                 console.error('HBC Jitsi: Invalid URL:', url, error);
                 return null;
             }
         },

         /**
          * Log meeting join for analytics
          */
         logMeetingJoin: function(bookingId, role) {
             if (typeof gtag === 'function') {
                 gtag('event', 'meeting_join', {
                     'booking_id': bookingId,
                     'user_role': role
                 });
             }
         },

         /**
          * Close modal
          */
         closeModal: function() {
             $('.hbc-modal').fadeOut(300, function() {
                 $(this).remove();
             });
         }
     };

     /**
      * Show meeting link in modal if popup is blocked
      */
     HBCJitsi.showMeetingLinkModal = function(meetingUrl, roomName) {
         // Sanitize inputs
         const safeUrl = this.sanitizeUrl(meetingUrl);
         const safeName = this.sanitizeInput(roomName) || 'Meeting';
         
         if (!safeUrl) {
             this.showError(hbc_jitsi.strings.invalid_url || 'Invalid meeting URL');
             return;
         }
         
         const modalHtml = `
             <div id="hbc-meeting-modal" class="hbc-modal">
                 <div class="hbc-modal-content">
                     <div class="hbc-modal-header">
                         <h3>${hbc_jitsi.strings.join_meeting || 'Join Meeting'}: ${safeName}</h3>
                         <span class="hbc-modal-close">&times;</span>
                     </div>
                     <div class="hbc-modal-body">
                         <p>${hbc_jitsi.strings.popup_blocked || 'Click the link below to join the meeting:'}</p>
                         <a href="${safeUrl}" target="_blank" rel="noopener noreferrer" class="hbc-meeting-link-btn">
                             <i class="fas fa-video"></i> ${hbc_jitsi.strings.join_meeting || 'Join Meeting'}
                         </a>
                         <p class="hbc-meeting-url">
                             <small>${hbc_jitsi.strings.meeting_url || 'Meeting URL'}: 
                                 <a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${safeUrl}</a>
                             </small>
                         </p>
                     </div>
                 </div>
             </div>
         `;
         
         $('body').append(modalHtml);
         $('#hbc-meeting-modal').fadeIn();
         
         // Close modal handlers
         $('.hbc-modal-close, #hbc-meeting-modal').on('click', function(e) {
             if (e.target === this) {
                 $('#hbc-meeting-modal').fadeOut(function() {
                     $(this).remove();
                 });
             }
         });
     };

    /**
     * Show embedded Jitsi meeting in modal (alternative approach)
     */
    HBCJitsi.showJitsiModal = function(roomName, meetingUrl) {
        // Sanitize inputs
        const safeUrl = this.sanitizeUrl(meetingUrl);
        const safeName = this.sanitizeInput(roomName) || 'Meeting';
        
        if (!safeUrl) {
            this.showError(hbc_jitsi.strings.invalid_url || 'Invalid meeting URL');
            return;
        }
        
        const modalHtml = `
            <div id="hbc-jitsi-modal" class="hbc-modal hbc-jitsi-modal">
                <div class="hbc-modal-content hbc-jitsi-modal-content">
                    <div class="hbc-modal-header">
                        <h3>${hbc_jitsi.strings.meeting || 'Meeting'}: ${safeName}</h3>
                        <span class="hbc-modal-close">&times;</span>
                    </div>
                    <div class="hbc-modal-body">
                        <div id="hbc-jitsi-container" class="hbc-jitsi-container"></div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#hbc-jitsi-modal').fadeIn();
        
        // Initialize Jitsi meeting in modal
        if (typeof JitsiMeetExternalAPI !== 'undefined') {
            this.initJitsiInModal(safeName, safeUrl);
        } else {
            // Fallback to direct link if API not available
            $('#hbc-jitsi-container').html(`
                <div class="hbc-jitsi-fallback">
                    <p>${hbc_jitsi.strings.api_unavailable || 'Click the link below to join the meeting:'}</p>
                    <a href="${safeUrl}" target="_blank" rel="noopener noreferrer" class="hbc-meeting-link-btn">
                        <i class="fas fa-video"></i> ${hbc_jitsi.strings.join_meeting || 'Join Meeting'}
                    </a>
                </div>
            `);
        }
        
        // Close modal handlers
        $('.hbc-modal-close, #hbc-jitsi-modal').on('click', function(e) {
            if (e.target === this) {
                $('#hbc-jitsi-modal').fadeOut(function() {
                    $(this).remove();
                });
            }
        });
    };

    /**
     * Initialize Jitsi meeting in modal container
     */
    HBCJitsi.initJitsiInModal = function(roomName, meetingUrl) {
        try {
            const domain = new URL(meetingUrl).hostname;
            
            const options = {
                roomName: roomName,
                width: '100%',
                height: 600,
                parentNode: document.getElementById('hbc-jitsi-container'),
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: false,
                    enableWelcomePage: false,
                    enableClosePage: false,
                    prejoinPageEnabled: false,
                    toolbarButtons: [
                        'camera',
                        'chat',
                        'closedcaptions',
                        'fullscreen',
                        'hangup',
                        'microphone',
                        'participants-pane',
                        'raisehand',
                        'settings',
                        'tileview',
                        'toggle-camera'
                    ]
                },
                interfaceConfigOverwrite: {
                    SHOW_CHROME_EXTENSION_BANNER: false,
                    SHOW_PROMOTIONAL_CLOSE_PAGE: false,
                    SHOW_POWERED_BY: false
                }
            };

            // Add user info if available
            const currentUser = hbc_jitsi.current_user;
            if (currentUser && currentUser.display_name) {
                options.userInfo = {
                    displayName: this.sanitizeInput(currentUser.display_name),
                    email: this.sanitizeInput(currentUser.email) || ''
                };
            }

            const api = new JitsiMeetExternalAPI(domain, options);
            
            // Handle meeting events
            api.addEventListener('readyToClose', function() {
                $('#hbc-jitsi-modal').fadeOut(function() {
                    $(this).remove();
                });
            });
            
            api.addEventListener('participantLeft', function(participant) {
                console.log('HBC Jitsi: Participant left:', participant);
            });
            
            api.addEventListener('participantJoined', function(participant) {
                console.log('HBC Jitsi: Participant joined:', participant);
            });
            
        } catch (error) {
            console.error('HBC Jitsi: Error initializing Jitsi:', error);
            // Fallback to direct link
            $('#hbc-jitsi-container').html(`
                <div class="hbc-jitsi-error">
                    <p>${hbc_jitsi.strings.load_error || 'Unable to load meeting interface. Please use the direct link:'}</p>
                    <a href="${meetingUrl}" target="_blank" rel="noopener noreferrer" class="hbc-meeting-link-btn">
                        <i class="fas fa-video"></i> ${hbc_jitsi.strings.join_meeting || 'Join Meeting'}
                    </a>
                </div>
            `);
        }
    };

    /**
     * Initialize meeting status updater
     */
    HBCJitsi.initMeetingStatusUpdater = function() {
        // Initial update
        this.updateMeetingStatus();
        
        // Auto-refresh meeting status every 30 seconds
        setInterval(() => {
            this.updateMeetingStatus();
        }, 30000);
    };

    /**
     * Update meeting button status based on current time
     */
    HBCJitsi.updateMeetingStatus = function() {
        $('.hbc-join-meeting-btn').each(function() {
            const $button = $(this);
            const $row = $button.closest('tr');
            const meetingDate = $row.find('.meeting-date').text();
            const meetingTime = $row.find('.meeting-time').text();
            
            if (meetingDate && meetingTime) {
                try {
                    const now = new Date();
                    const meetingDateTime = new Date(meetingDate + ' ' + meetingTime);
                    
                    if (isNaN(meetingDateTime.getTime())) {
                        console.warn('HBC Jitsi: Invalid meeting date/time:', meetingDate, meetingTime);
                        return;
                    }
                    
                    const timeDiff = meetingDateTime.getTime() - now.getTime();
                    
                    // Update button state based on time
                    // TESTING MODE: Always enable meeting buttons regardless of time
                    // This bypasses all time-based restrictions for immediate access
                    if (!$button.hasClass('hbc-btn-active')) {
                        $button.removeClass('hbc-btn-ready').addClass('hbc-btn-active');
                        $button.find('i').removeClass('fa-clock').addClass('fa-video');
                    }
                    
                    /* Original time-based logic (commented out for testing):
                    if (timeDiff <= 0) {
                        // Meeting time has passed or is current
                        if (!$button.hasClass('hbc-btn-active')) {
                            $button.removeClass('hbc-btn-ready').addClass('hbc-btn-active');
                            $button.find('i').removeClass('fa-clock').addClass('fa-video');
                        }
                    } else if (timeDiff <= 900000) { // 15 minutes
                        // Meeting is within 15 minutes
                        if (!$button.hasClass('hbc-btn-ready')) {
                            $button.addClass('hbc-btn-ready');
                            $button.find('i').removeClass('fa-clock').addClass('fa-video');
                        }
                    }
                    */
                } catch (error) {
                    console.error('HBC Jitsi: Error updating meeting status:', error);
                }
            }
        });
    };

    // Initialize when document is ready
    $(document).ready(function() {
        HBCJitsi.init();
    });

})(jQuery);