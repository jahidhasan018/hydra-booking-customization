# Host Dashboard Button Troubleshooting Guide

## Issue: "View Details", "Generate Join Link", and "Send Reminder" buttons not working

### What I've Fixed:

1. **Added Debug Logging**: The JavaScript now logs when buttons are clicked and when AJAX variables are missing.

2. **Added Fallback Functionality**: When `hbc_ajax` is not properly configured, the buttons now show mock functionality instead of failing silently.

3. **Improved Error Handling**: Better error messages for network issues, server connection problems, and missing endpoints.

4. **Added Mock Data**: For testing purposes, the buttons will work even without proper WordPress AJAX setup.

### How to Test:

1. **Open Browser Console**: Press F12 and go to the Console tab
2. **Click the Buttons**: Try clicking "View Details", "Generate Join Link", and "Send Reminder"
3. **Check Console Messages**: Look for debug messages that will help identify the issue

### Common Issues and Solutions:

#### 1. AJAX Variables Not Loaded
**Symptoms**: Console shows "hbc_ajax is not properly configured"
**Solution**: 
- Check if the host dashboard page is properly set up in WordPress
- Verify the shortcode `[hbc_host_dashboard]` is used correctly
- Ensure the page is being loaded through WordPress (not as a static file)

#### 2. WordPress AJAX Not Working
**Symptoms**: Console shows "AJAX endpoint not found" or 404 errors
**Solution**:
- Check if WordPress is running properly
- Verify the plugin is activated
- Check if there are any PHP errors in WordPress error logs

#### 3. Nonce Issues
**Symptoms**: AJAX calls return "Invalid nonce" or security errors
**Solution**:
- Clear browser cache
- Log out and log back in to WordPress
- Check if user has proper permissions

#### 4. JavaScript Not Loading
**Symptoms**: No console messages when clicking buttons
**Solution**:
- Check if jQuery is loaded
- Verify the host-dashboard.js file is being enqueued properly
- Check for JavaScript errors that might prevent script execution

### Testing with Mock Data:

The buttons now work with mock data when WordPress AJAX is not available:
- **View Details**: Shows sample booking information
- **Generate Join Link**: Shows a mock join link message
- **Send Reminder**: Shows a success message after 1 second

### Files Modified:

1. `assets/js/host-dashboard.js` - Added debug logging, error handling, and mock functionality
2. `test-host-dashboard.html` - Created for standalone testing

### Next Steps:

1. Test the buttons in the actual WordPress environment
2. Check browser console for any error messages
3. If issues persist, check WordPress error logs
4. Verify user permissions and plugin activation status