@echo off
::User Doc
rmdir /S /Q ".\crudkit-apidoc-user"
php -d error_reporting=1 phpDocumentor.phar -d "..\src" -t ".\crudkit-apidoc-user" --ignore "examples/,views/,config/" --title "CRUDKit User API Doc" --sourcecode  > .\phpdoc-log-user.txt

::Developer Doc
rmdir /S /Q ".\crudkit-apidoc-dev"
php -d error_reporting=1 phpDocumentor.phar -d "..\src" -t ".\crudkit-apidoc-dev" --parseprivate --ignore "examples/,views/,config/" --title "CRUDKit Developer API Doc" --sourcecode > .\phpdoc-log-dev.txt

::Copy to /public
if exist "..\..\..\..\public" (	
	rmdir /S /Q "..\..\..\..\public\crudkit-apidoc-dev"
	rmdir /S /Q "..\..\..\..\public\crudkit-apidoc-user"
	xcopy ".\crudkit-apidoc-dev" "..\..\..\..\public\crudkit-apidoc-dev\" /c /q /e /h /y
	xcopy ".\crudkit-apidoc-user" "..\..\..\..\public\crudkit-apidoc-user\" /c /q /e /h /y
)