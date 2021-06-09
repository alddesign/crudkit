# API Documentation (apidoc)
## First things first
**Dont read the apidoc before...** you have read [readme-howto](./readme-howto.md) and checked out the sample code in the `CrudkitServiceProvider.php`. You have to know the basics about how to define your tables,pages,relations and users.

The apidoc gives you a deeper insight into that. Its a documentation about the classes and methods used to build your `CrudkitServiceProvider.php`. For example: Lookups, ManyToOnes, custom Actions and Ajax columns may be a bit confusing - here is the place to learn more about them. 

Straigh outta code - generated with [phpDocumentor2](https://phpdoc.org/) - thanks to you guys.

**CRUDKit grows, so does the apidoc.**\
In the first run my goal is to cover the whole project with apidoc. My next step will be to refine that by adding more detail.
With every commit there will be more documentation, examples, explaination and detail.

## Where to find
The apidoc can be found under `<laravel root director>/vendor/alddesign/curdkit/doc`
There are two versions
- `doc/crudkit-apidoc-user` contains documentation about all the classes/methods you normally need to build webpages with crudkit
- `doc/crudkit-apidoc-dev` contains the user-doc, plus a basic documenatation about the rest of crudkit

## Opening the apidoc
The simple way:
* Open the `index.html` file

Accessing it via a websever alongside with CRUDKit. As you need a webserver to run CRUDKit this is no big deal (This is already done in crudkit-standalone):
* Copy the folders `crudkit-apidoc-user` and/or `crudkit-apidoc-user` to `<laravel root director>/public` directory.
* Now they are accessible by opening `http://<APP_URL>/crudkit-apidoc-user/index.html` or `http://<APP_URL>/crudkit-apidoc-dev/index.html`
