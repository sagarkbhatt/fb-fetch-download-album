# Facebook-Album Challenge
This web application authenticates user using Facebook authentication and fetches user's album data.
It allows user to download album with different options.
User can have options to download particular album,multiple albums or all albums.
Whenever user clicks on one of the download option, User can download album in .zip format via unique link.

## Code Example
As a first mandatory step, change configuration file named as ``fgConfig.PHP`` with following code snippet:

Code snippet:

```
$fb = new Facebook\Facebook([
'app_id' => 'Your app-id', 
'app_secret' => 'Your app-secret',
'default_graph_version' => 'v2.7'

]);

```
In this project, application needs user's permissions to get access of user's album data.
Application is making a call to Facebook Graph API to get necessary data.
To control access of data you can have options which requires to be setup in home.PHP file.

By setting up home.PHP, resonse will be retrieved in json format as below:

Code snippet:

```
$response = $fb->get('me/albums?fields=cover_photo,photo_count,photos{link,images},picture{url},name');

```
Once data got retrieved,application redirects user to ``main.html`` which is responsible to manipulate data as per requirements.

All client side logic is getting executed by ``AngularJs``. 

There are main three functions:
1. Download
2. DownloadSelected
3. DownloadAll 

These functions take some processed data and pass the same to PHP api.
PHP api fetches, downloads content to server and provides a unique user's album link which contains downloadable .zip format file. 

Code snippet:

```
$download_file=file_get_contents($file);
//Get content(image) from url

file_put_contents($name,$download_file);
//stores data in server
//$name is name of file

$zip->addFile($name);
//Adds file in zip

```
### Prerequisites
1)PHP Version 5.5 
2)Mb string support

### Third party libraries
1)Facebook PHP sdk V5
2)AngularJS V1
3)Jquery
4)Bootstrap
5)Angular-loading-bar 
6)Jquery slide show

## Running the tests
Explain how to run the automated tests for this system

### Break down into end to end tests

Explain what these tests test and why

```
Give an example
```


## Deployment

Add additional notes about how to deploy this on a live system

## Built With

* Dropwizard - Bla bla bla
* Maven - Maybe
* Atom - ergaerga

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Billie Thompson** - *Initial work* - [PurpleBooth](https://github.com/PurpleBooth)

See also the list of [contributors](https://github.com/your/project/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Hat tip to anyone who's code was used
* Inspiration
* etc
