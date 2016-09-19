# Facebook-Album Challenge
### [Live Demo](htttp://sagarkbhatt.me/)

This web application authenticates user using Facebook authentication and fetches user's album data.
It allows user to download album with different options.
User can have options to download particular album,multiple albums or all albums.
Whenever user clicks on one of the download option, User can download album in .zip format via unique link.

## Code Example
As a first mandatory step, change configuration file named as **fgConfig.PHP** with following code snippet:

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
Once data got retrieved,application redirects user to **main.html** which is responsible to manipulate data as per requirements.

Most of client side logic is build with the help of **AngularJs Framework**. 

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

1. PHP 5.4 or greater 
2. The mbstring extension

Apart from this you have to also check following configuration in your php config file.

1. allow_url_include = On
2. allow_url_fopen = On


### Third party libraries

1. Facebook PHP sdk V5
2. PHPUnit 5.5
3. AngularJS V1
4. Jquery
5. Bootstrap
6. Angular-loading-bar 
7. Jquery slide show

## Running the tests

To run test cases,You have to first install PHPUnit.

Code snippet:

```
1. wget https://phar.phpunit.de/phpunit.phar

2. chmod +x phpunit.phar

3. sudo mv phpunit.phar /usr/local/bin/phpunit

```
After installation,Go to project directory and run following command in terminal.

```
phpunit tests/

``` 

## Built With

* Microsoft visual studio code.
* Git

## Author

#**Sagar Bhatt**
##[Github Profile](https://github.com/sagarkbhatt)
