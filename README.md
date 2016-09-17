# Facebook-Album Challenge

This web application authenticates usr using Facebook authentication and fetches user's album data.
It allows user to download album with different options.
User will be given options to download particular album,multiple album and all album.
As user clicks on one of the download option, User will be able to download his album from unique link in .zip format.

## Code Example

You have to first chage configuration file ``fgConfig.php`` to run project successfully.

```
$fb = new Facebook\Facebook([
'app_id' => 'Your app-id', 
'app_secret' => 'Your app-secret',
'default_graph_version' => 'v2.7'

]);

```
In this project I needs user's permission to get access of user's album.
Here I made a call to Facebook graph api to get user's album information.
If you need more or less information you can edit this line in home.php
You will get your response in php format.

```
$response = $fb->get('me/albums?fields=cover_photo,photo_count,photos{link,images},picture{url},name');

```

  


### Prerequisities

What things you need to install the software and how to install them

```
Give examples
```

### Installing

A step by step series of examples that tell you have to get a development env running

Stay what the step will be

```
Give the example
```

And repeat

```
until finished
```

End with an example of getting some data out of the system or using it for a little demo

## Running the tests

Explain how to run the automated tests for this system

### Break down into end to end tests

Explain what these tests test and why

```
Give an example
```

### And coding style tests

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
