Bolt Match All routes
======================

This extension can be used to create a match all route for multiple contentTypes.

So if you have two contentTypes, eg 'places' and 'countries' and you want them 
to be accessible through 
- /amsterdam
- /netherlands

This controller will make this happen.

Standard Bolt would mean the URL:
- /places/amsterdam
- /countries/netherlands

## Configuration ##
The configuration is not done by a config.yml file yet, this is still to be done.
For now you need to edit the source code:
`/src/Controllers/MatchAllController.php`

Edit `line 34` and add the contentType to the array.
```
$contentTypes = ['countries','places'];
```
## ToDo ##
- Make the contentTypes configurable trough config.yml
> Feel free to contribute to this repository