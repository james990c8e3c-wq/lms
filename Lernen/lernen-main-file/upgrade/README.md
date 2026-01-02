# Upgrading Lernen to 3.0.9

Please read this guide carefully before upgrading **Lernen** from version **2.0.3** or lower to **2.1.8**. This is a major release that introduces significant structural changes, including transitioning add-ons from **packages** to **modules**. Following these steps will ensure a smooth upgrade process.

---

## Important Steps Before Upgrading

1. **Replace the `Upgrade.php` File**  
   Locate the existing `Upgrade.php` file in your project at:  
   ```
   public_html/app/Livewire/Pages/Admin/Upgrade.php
   ```  
   Replace this file with the updated version found in the downloaded zip package inside upgrade directory at:  
   ```
   upgrade/Upgrade.php
   ```  
   This file contains essential updates necessary for the upgrade process. This action only required if your app version is below 2.1.0.
   
2. **Replace the `Modules` Directory**
   Locate Moduels.zip inside upgrade folder & unzip to root directory only if your app version is below 2.1.0.      

3. **Server Configuration Requirements**  
   Ensure your server settings meet the following requirements to handle the upgrade successfully:

   - **File Upload Size Limit**:  
     Update `upload_max_filesize` to be **512 MB** or higher.  
     Example for `php.ini`:  
     ```ini
     upload_max_filesize = 512M
     ```

   - **Post Data Size Limit**:  
     Update `post_max_size` to be **512 MB** or higher.  
     Example for `php.ini`:  
     ```ini
     post_max_size = 512M
     ```

   You can check these settings by running:  
   ```php
   phpinfo();
   ```

4. **Backup Your Project**  
   Before proceeding with the upgrade, it's highly recommended to back up your:

   - **Database**
   - **Codebase**
   - **Environment Configuration (`.env` file)**

---

## Important Note:

Step 1 & 2 is required only if your app version is below 2.1.0. After upgrading to 2.1.8 you need to install all the addons if your version was below 2.1.0.

## Need Help?

If you encounter any issues during the upgrade process, please don't hesitate to reach out to our support team. You can provide your hosting details, and we'll assist you in resolving the issue.

Thank you for using **Lernen LMS**!  
**The Lernen Support Team**

--- 

This revised README provides more context, additional steps, and recommendations to help users upgrade smoothly and avoid potential pitfalls.