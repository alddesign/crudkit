# API Documentation (api-doc)
## First things first
**Dont read the api-doc before...** you have read [readme-howto](./readme-howto.md) and checked out the sample code in the `CrudkitServiceProvider.php`. You have to know the basics about how to define your tables,pages,relations and users.

The api-doc gives you a deeper insight into that. Its a documentation about the classes and methods used to build your `CrudkitServiceProvider.php`. For example: Lookups, ManyToOnes, custom Actions and Ajax columns may be a bit confusing - here is the place to learn more about them. 

**CRUDKit grows, so does the api-doc.**\
In the first run my goal is to cover the whole project with api-doc. My next step will be to refine that by adding more details.
With every commit there will be more documentation, examples, explaination and detail.

## Where to find
Its a static HTML page which comes in two versions:
- `api-doc/user/index.html` a reduced version of the documentation. Includes only the most important classes and methods.
- `api-doc/dev/index.html` the extended version. Covers almost every corner of crudkit. If you want to know even more, you need to check the source code.

The api-doc comes straigh outta code, generated with [phpDocumentor3](https://docs.phpdoc.org/3.0/).

## Opening the api-doc
Open the `index.html` file.