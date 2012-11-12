<?php

class subscriber_password {
}

/* not a code test, but the story board for (manually) testing subscriber password functionality
 * 
 * 
 * Scenario A. config ASKFORPASSWORD = true
 * 
 * 1. Sign up with password, and confirm by clicking link in request for confirmation message
 * 2. Click preferences link, should ask for password
 * 2 A. Enter incorrect password, should give error
 * 2 B. Enter correct password, should allow in
 * 
 * At this stage, the "login status" is remembered in the session, so CLEAR COOKIES
 * 
 * 3. Go back to subscribe page
 * 4. Sign up with same email address, but different password
 * 4 A. Should give error, user exists, with different password
 * 5. Sign up with same email address, and same password
 * 5 A. Signing up should work as normal, requesting confirmation
 * 6. Confirm subscription by clicking link in email
 * Welcome email received
 * 7. Click "Preferences" in welcome email -> Should prefill email and ask for password
 * 8. Do not enter password and click "Unsubscribe" link in email -> Should put up page to ask why they want to unsubscribe
 * 9. Click "Unsubscribe"
 * Goodby email received
 * 
 * 
 * Scenario B. config ASKFORPASSWORD = true, config UNSUBSCRIBE_REQUIRES_PASSWORD = true
 * 
 * All of the above, except 9:
 * Goes to page asking for password
 * 10 A. enter wrong password - should give error
 * 10 B. enter correct password - should give the normal unsubscribe question 
 * 
 * 
 * Scenario A + B
 * 
 * 11. Go to preferences page, login and change password
 * 
 * CLEAR COOKIES
 * 
 * 12. Go to preferences page again. 
 * 12 A. Login with old password, should return error invalid password. 
 * 12 B. Login with new password, should give the preferences page.
 * 
 * Scenario C - Forgot password
 * 
 * 13. Enter as admin and load a subscriber profile
 * 14. Enter something in the password box and click save
 * 
 * CLEAR COOKIES
 * 
 * 15. Go back to 1 and sign in with changed password
 * 
 */
