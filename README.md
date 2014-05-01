BentoEsque
==========

Automatically updated program pages with upcoming episodes and latest COVE video.

Installation
============
- Edit the *iframe.php* and set the variables for **$schedule_API**, **$cove_API**, and **$cove_secret**.
- Upload *iframe.php* and the */library/* subfolder to your local web server.
- Create a smart snippet (*see snippet.txt*), and change the URL path variables to match the location of your server.
- Add the smart snippet to your Bento page and go!

Smart Snippet Usage
===================
**Required:**
- Callsign - *your station's call letters*
- Display_Description - *true or false, to display the show's description (only if View_Type = 1)*
- Display_Title - *true or false, to display the show's title (only if View_Type = 1)*
- Program - *select either a national program or "Other"*
- Video_Portal_URL
- View_Type - *View_Type = 1, for main main content areas, and View_Type = 2, for a Right rail widget for related videos

**Optional:**
- Other_Cove_ID - *Only used if Program type is "Other" to add your own local shows*
- Other_Schedule_ID - *Only used if Program type is "Other" to add your own local shows*