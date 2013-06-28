# VWM Polls

VWM Polls is a polling module & fieldtype for ExpressionEngine 2 (version 2.6.0 and up).

## License

VWM Polls is licensed under the [Apache 2 License](http://www.apache.org/licenses/LICENSE-2.0.html).

## Support (in order of preference)

I provide the following support options:

* [GitHub Wiki](https://github.com/vmichnowicz/vwm_polls/wiki)
* [GitHub Issue Tracker](https://github.com/vmichnowicz/vwm_polls/issues)
* [Devot:ee](http://devot-ee.com/add-ons/vwm-polls)
* [Personal contact form](http://www.vmichnowicz.com/contact)
* [Twitter](http://twitter.com/vmichnowicz)

## Video Tutorial (Installation and Configuration)

A quick video tutorial detailing the installation and configuration of VWM Polls (version 0.4.2) on ExpressionEngine 2.4 can be found on [Vimeo](http://vimeo.com/vmichnowicz/vwm-polls-installation-and-configuration)

## Example Code

Please visit the [wiki](https://github.com/vmichnowicz/vwm_polls/wiki) for the latest installation instructions and example code.

## Variables
{exp:vwm_polls:poll entry_id="{entry_id}" field_id="{poll_1:field_id}" redirect="index"} - Supplies the form tag
* {already_voted} - If the user already voted
* {can_vote} - If the user can vote
* {chart} - The image URL for the Google Chart
* {input_name} - The name for the input (currently always vwm_polls_options[])
* {input_type} - Type of input (checkbox or radio)
* {max_options} - The max number of options that can be selected (or 0 for no maximum)
* {min_options} - The minimum number of options that must be selected (or 0 for no minimum)
* {total_votes} - The total number of votes
* {options} - Loop of the options
	* {color} - The color for the option
	* {id} - Option ID
	* {order} - The order for the option
	* {other_name} - Name for the text field for the user to enter the other option text
	* {text} - Option Text
	* {type} - "defined" or "other"
	* {user_vote} - If this is the users vote
	* {votes} - The number of votes
* {options_results} - Loop of the options with the results
	* Everything under {options}
	* {percent} - The percent of the votes for this item as 2 digits (eg 98)
	* {percent_decimal} - The percent of the votes as a non-rounded decimal (eg 0.3141569)


## Video Tutorial (Installation and Configuration)

A quick video tutorial detailing the installation and configuration of VWM Polls (version 0.4.2) on ExpressionEngine 2.4 can be found on [Vimeo](http://vimeo.com/vmichnowicz/vwm-polls-installation-and-configuration)

## Change Log

### 0.8

* Custom JavaScript unique user checks
* Requires ExpressionEngine 2.6.0 or higher
* Add option to remove chart labels from Google chart
* Now using ExpressionEngine Localize class introduced in 2.6.0
* Cleaned up code

### 0.7

* Support for IPv6
* Added config option to disable IP address checking

### 0.6

* Removed the use of AJAX in the field type
* Added an improved color picker and allowed direct entry of a color
* Added an AJAX event to just fetch results

### 0.5.3

* Fixed bug that restricted poll access by guest users

### 0.5.2

* Fixed poll expiration logic

### 0.5.1

* Added default values for MySQL poll options table

### 0.5

* Added simple color picker for poll options
* Added sexy pill toggle for "defined" and "other" poll options
* More granular group permissions allowing for easy selection of "All" member groups or "None", instead of having to select each allowed member group manually like before
* Super hottt "+" (add option) button
* Web 3.0 enhancements
* Web 2.0 deprecation

### 0.4.2

* Added `add_package_path()` to class constructors in order to make sure the package path was being defined by EE.

### 0.4.1

* Moved some code around so the `Unable to load the requested file: helpers/vwm_polls_helper.php` *should* no longer be a problem.