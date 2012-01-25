This is a collection of miscellaneous classes that I've built over the years to help me accomplish simple tasks.  

Most of these are simple data models, and none of them merit a whole repo just for themselves, so into this collection they go.

###Browser

This is a basic static class for testing the visitor's user-agent against certain browsers.

###ColorWheel

Simple class for getting an array of color hex values spread across the entire spectrum.

###Console

PHP equivalent of javascript's console.log, but sends to the system log.

###CreditCard

Data model for validating credit card information from form submissions and detecting card type.

###DateRange

Simple object for storing a date range.  Includes static functions for getting common ranges such as This Week, Last Week, Last Month, etc.

###File

Object wrapper for all of PHP's file manipulation functions.

###Geography

Contains two static arrays, $COUNTRIES and $STATES.  

Geography::$COUNTRIES is a list of all countries of the world, keyed to their two character code. By default it places the United States and the United Kingdom at the top of the list.

Geography::$STATES is a two-level array of states, provinces and prefectures for any countries large enough to include that value in the mailing address (and a few that don't).  This collection is a work in progress and currently contains collections for the following countries:

- United States
- Canada
- Mexico
- Australia
- United Kingdom (counties)
- Japan
- India
- Germany
- China

This collection is organized by `$STATES[country code][abbreviated] = full name`.

###ImageFile (requires File)

Wrapper class for Imagemagick's convert utility. Note that this does not use the imagemagick PHP bridge, but rather executes `convert` directly via the shell.  Includes code to locate the convert utility on the server (does not support Windows servers).  Provides functions for performing resizing, rotation, cropping and format conversion.

###PhoneNumber

Data model for sanitizing and reformatting US phone numbers (reformatting is hard-coded for US standard `(XXX) XXX-XXXX`).

###RelativeDate

Data model for generating a relative date from a timestamp (ie "Today", "Five Days Ago", "In Two Weeks").  Needs to be rewritten to use DateTime.


Copyright (c) 2011-2012, Jarvis Badgley - chiper[at]chipersoft[dot]com

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
