# link

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

## Installation

Just copy the `public` folder contents to your webhosting root directory and open its URL in a browser. Note: your webserver needs write access to that directory.

A config file named `config.php` with random passwords and file names will be generated, and you will be ready to go. You can edit the config file if you wish.

**Pull requests are welcome.**
