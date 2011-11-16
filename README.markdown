# Google Plus status update bot

* Readme date: Nov 16 2011
* Contributors: lukapusic
* Author: Luka Pusic <pusic93@gmail.com>
* URI: http://360percents.com/posts/first-google-google-plus-status-update-bot-in-php/

## Description
This bot can log into your Google account and update your Google Plus status,
but you can extend it to other Google products. All this is done without Google API,
OAuth, tokens or any other annoying products.


## System requirements
* PHP curl extension

## Instructions
1. Open gplus.php and edit email and password
2. run it ```php gplus.php```

## Changelog
Nov 11 2011
* added debug parameter, pageid parameter, pc_uagent parameter
* page updating still not implemented
Nov 16 2011
* changed the way baseurl is determined, google remove base href

## Known issues
* fails if you didn't confirm mobile location terms and conditions
* fails if you have mobile verification enabled

## License
 ----------------------------------------------------------------------------
 "THE BEER-WARE LICENSE" (Revision 42):
 <pusic93@gmail.com> wrote this file. As long as you retain this notice you
 can do whatever you want with this stuff. If we meet some day, and you think
 this stuff is worth it, you can buy me a beer in return. Luka Pusic
 ----------------------------------------------------------------------------
