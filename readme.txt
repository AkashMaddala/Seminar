Project Overview

Project Structure & File Documentation

 1. Database Configuration
- `db.php` - Database connection handler
  - Contains database credentials
  - Provides `get_db_connection()` function used throughout the project
  - Manages MySQL connection

 2. User Authentication System

 `auth.php` - Authentication controller
- Handles both login and registration logic
- Routes users based on `?action=` parameter (login/register)
- Validates user credentials
- Creates sessions for authenticated users
- Special handling for admin login (username: admin, password: asdfgh)
- Redirects admins to admin dashboard, regular users to homepage

 `login_form.php` - Login interface
- Simple login form with email/username and password fields
- Displays error/success messages
- Links to registration page

 `register_form.php` - Registration interface
- Registration form with name, email, and password fields
- Basic validation (email format, password length)
- Links to login page

 3. Main Application Interface

 `index.php` - Main entry point
- Checks user session
- Includes layout template
- Sets page title and content based on login status

 `layout.php` - Master template
- Provides consistent HTML structure
- Includes navigation header
- Renders dynamic content from other files
- Handles responsive design

 `home_content.php` - Dynamic homepage content
- For Guests:
  - Welcome message
  - Email-based booking check feature
  - Login/Register buttons
  
- For Logged-in Users:
  - Personalized dashboard
  - "View My Bookings" button
  - "Load Available Seminars" button
  - Admin-only "Create Seminar" button
  
- Contains two modals:
  - Booking confirmation modal (for users)
  - Create seminar modal (for admin)
  
- JavaScript functions for:
  - AJAX seminar loading
  - Modal management
  - Admin conflict checking
  - Form validation

 4. Admin Functionality

 `admin_dashboard.php` - Admin control panel
- Admin-only access (redirects non-admins)
- Create Seminar Form:
  - Title, description, date, time, capacity inputs
  - Real-time conflict checking
  - Validates no time overlaps with existing seminars
- Seminars Table:
  - Lists all seminars with details
  - Shows ID, title, date, time, capacity

 `create_seminar.php` - Seminar creation handler
- Validates admin authentication
- Checks for time conflicts (seminars are 1-hour blocks)
- Creates new seminar in database
- Redirects with success/error messages

 `check_seminar_times.php` - AJAX conflict checker
- Returns existing seminars for a given date
- Used by admin form to show conflicts in real-time

 5. Booking System

 `load_seminars.php` - AJAX seminar loader
- Fetches upcoming seminars from database
- Shows booking status for each seminar
- Displays capacity and waitlist information
- Creates seminar cards with "Book Now" buttons
- Disables booking for already registered seminars

 `actions.php` - Main action handler
Handles three primary actions:

1. `action=book` - Process booking
   - Validates user is logged in
   - Checks for duplicate bookings
   - Checks for time conflicts with other bookings
   - Auto-waitlists if seminar is full
   - Creates booking record
   - Sends confirmation email

2. `action=cancel` - Cancel booking
   - Verifies booking ownership
   - Updates booking status to 'canceled'
   - Promotes first waitlisted user if spot opens
   - Sends cancellation/promotion emails

3. `action=logout` - User logout
   - Destroys session
   - Redirects to login page

 `my_bookings.php` - User's bookings display
- Shows all user's current bookings
- Groups by status (Confirmed/Waitlisted)
- Displays seminar details and booking time
- Includes cancel buttons for each booking

 `check_bookings.php` - Guest booking checker
- Allows non-logged users to check bookings by email
- Shows all bookings associated with an email
- Displays cancellation links

 6. Email System

 `send_email.php` - Email notification handler
- Uses PHPMailer library
- Sends four types of emails:
  1. Booking confirmation - with cancellation link
  2. Waitlist confirmation - when seminar is full
  3. Promotion notification - when moved from waitlist
  4. Cancellation confirmation - when booking cancelled
- Includes seminar details and time slot in all emails
- Logs all email activities to `email_log.txt`

 7. Cancellation System

 `cancel_booking.php` - Token-based cancellation
- Handles cancellations via email links
- Validates cancellation token
- No login required (uses secure token)
- Updates booking status
- Handles waitlist promotions

 8. Supporting Files

 `check_admin_conflicts.php` - Admin time validation
- AJAX endpoint for real-time conflict checking
- Returns conflict information for selected date/time

 `get_time_slots.php` - (Legacy/Simplified)
- Originally showed multiple time slots
- Now simplified since seminars have fixed times

 9. Third-party Components

 `PHPMailer/` - Email library folder
- Contains PHPMailer classes for email functionality
- Handles SMTP authentication with Gmail

 Data Flow Example

 User Booking Flow:
1. User logs in via `auth.php`
2. Lands on `index.php` → `home_content.php`
3. Clicks "Load Seminars" → AJAX call to `load_seminars.php`
4. Clicks "Book Now" → Modal opens with seminar details
5. Confirms booking → Form submits to `actions.php?action=book`
6. `actions.php` validates and creates booking
7. `send_email.php` sends confirmation email
8. User redirected with success message

 Admin Seminar Creation Flow:
1. Admin logs in → redirected to `admin_dashboard.php`
2. Fills create seminar form
3. `check_admin_conflicts.php` validates time availability
4. Submits to `create_seminar.php`
5. Seminar created in database
6. Admin sees success message

 Key Features Implemented

1. User Authentication - Registration, login, session management
2. Role-based Access - Admin vs regular user permissions
3. Seminar Management - Admin can create, users can view
4. Booking System - With automatic waitlisting
5. Conflict Prevention - No double bookings or time overlaps
6. Email Notifications - For all booking actions
7. Cancellation System - Both in-app and via email tokens
8. Guest Access - Check bookings without login
9. Responsive Design - Works on desktop and mobile