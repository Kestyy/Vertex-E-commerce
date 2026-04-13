<?php
/**
 * Email Configuration
 * 
 * Set up SMTP credentials to enable email verification
 * 
 * === OPTION 1: GMAIL (Easiest) ===
 * 1. Go to myaccount.google.com/apppasswords
 * 2. Select "Mail" and "Windows (or your device)"
 * 3. Copy the 16-character app password
 * 4. Fill in your Gmail address and the app password below
 * 
 * === OPTION 2: MAILTRAP (Best for testing) ===
 * 1. Sign up free at mailtrap.io
 * 2. Go to Email Sending > Gmail
 * 3. Copy the SMTP credentials shown
 * 4. Fill them in below
 * 
 * === OPTION 3: Other Email Services ===
 * Configure the SMTP host, username, and password for your provider
 */

// SMTP Configuration
putenv('SMTP_HOST=smtp.gmail.com');
putenv('SMTP_PORT=587');
putenv('SMTP_USERNAME=yutzu362@gmail.com'); 
putenv('SMTP_PASSWORD=lcnbxbcnbceduuuv'); 
putenv('SMTP_FROM_EMAIL=yutzu362@gmail.com');
putenv('SMTP_FROM_NAME=Vertex Ecommerce');

// Enable debug mode to see SMTP errors
putenv('SMTP_DEBUG=true');
