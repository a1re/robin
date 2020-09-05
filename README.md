American football game data grabber. Public version [available here](http://robin.firstandgoal.in/).

# How to use it
Put game URL like https://www.espn.com/nfl/game/_/gameId/401131047 into input,
click "Parse" and enjoy structured data.

## Websites supported
- ESPN.com

# Changelog
All notable changes to this project will be documented in this file.

## v1.1 - 2019-12-16
- Fixed bug with surnames with dot on the end (e.g. Jr., Sr.),
- Enhanced interface of dictionary updating.

## v1.0 - 2019-12-16
- Fancy look,
- Templates for output data,
- Copying data by click,
- Visual interface of adding new data to dictionary,
- Objects moved to separate folder,
- Converted functions to modern PHP Object model,
- Code cleaned and formatted to PSR-2.

## v0.1 - 2019-08-04
- Parsing Gamecast pages from ESPN.com
- Converting game leaders, scoring summary and quarters score to EasyTables Format
- Displaying data in copy-and-paste format
- Output of log events
- Keeping translations in .ini files

# TODO
- Moving dictionary MySQL,
- Authorisation,
- Add checkboxes into request for with specifying what data to parse,
- Supporting NFL.com and other data sources,
- Parsing gaming lists (like all games from the week),
- Enhance templating and make different versions of output formats.