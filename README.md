# link - URL Shortening Service in a single PHP file!

This is the simplest possible PHP-based URL shortener for personal use.

I have been looking for this all over the internet. All solutions were way too big and cumbersome, so I ended up reinventing the wheel.

## Special features

Here are the reasons I couldn't use any other solutions:
- Super super simple:
    - The entire actual programming code is inside a single PHP file.
    - Links are kept locally in an SQLite Database.
- Links can be configured to first show where they are going. If installed on `https://example.com`, then visiting `https://example.com/someLink` will first display a page showing the visitor the destination URL, and a "go!" button. "direct" links will still directly be redirected instead.
- Visiting the root (`https://example.com` in the example above) without being logged in displays a list of all links with the `public` flag.
- visits are recorded in local CSV log files for further analysis if needed.
- The page is a progressive web app (PWA): you can "save to homescreen" on your mobile to use it like a native app.

## Super Simple Installation

Just copy the `index.php` file to your webhosting root directory and open its URL in a browser. Note: your webserver needs full write access to that directory.

The following files will be generated upon first calls:
- A config file named `config.php` with random passwords and file names
- A `*.db` file for your links sqlite database
- A `.htaccess` file for all the redirection and caching magic
- A `log-*.csv` file will be generated the first time one of your shortcut links is visited.

You can adjust any of the files to your needs if you know what you are doing.

**Pull requests are welcome.**
