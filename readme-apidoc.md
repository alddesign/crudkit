# API Documentation (apidoc)
Curdkit comes with documentation of its API.  
Straigh outta code - generated with [phpDocumentor2](https://phpdoc.org/) - thanks to you guys.

**Crudkit grows, so does the apidoc.**  
In the first run its my goal to cover the wohle code with the apidoc.  
With every commit there will be more documentation: more examples, explainations and details.

## Where to find
The apidoc can be found under `<laravel root director>/vendor/alddesign/curdkit/doc`
There are two versions
- `doc/crudkit-apidoc-user` contains documentation about all the Classes/methods you need as a "user" to build webpages with crudkit
- `doc/crudkit-apidoc-dev` contains all the things that are in the user doc + a basic documenatation about the rest of crudkit

## Open the apidoc
Although the apidocs are html/css webpages make sure you access the via a webserver.
As you need a webserver to run crudkit this is no big deal (already done in crudkit-standalone):
* Copy the folders `crudkit-apidoc-user` and/or `crudkit-apidoc-user` to `<laravel root director>/public` directory.
* Now they are accessible by opening `http://<APP_URL>/crudkit-apidoc-user/index.html` or `http://<APP_URL>/crudkit-apidoc-dev/index.html`
