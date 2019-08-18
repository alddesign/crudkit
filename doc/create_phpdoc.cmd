@echo off
::User Doc
rmdir /S /Q ".\crudkit-apidoc-user"
php -d error_reporting=1 phpDocumentor.phar -d "..\src" -t ".\crudkit-apidoc-user" --title "CRUDKit User API Doc" --sourcecode -q

::Developer Doc
rmdir /S /Q ".\crudkit-apidoc-dev"
php -d error_reporting=1 phpDocumentor.phar -d "..\src" -t ".\crudkit-apidoc-dev" --parseprivate --title "CRUDKit Developer API Doc" --sourcecode -q

::Copy to /public
if exist "..\..\..\..\public" (	
	rmdir /S /Q "..\..\..\..\public\crudkit-apidoc-dev"
	rmdir /S /Q "..\..\..\..\public\crudkit-apidoc-user"
	xcopy ".\crudkit-apidoc-dev" "..\..\..\..\public\crudkit-apidoc-dev\" /c /q /e /h /y
	xcopy ".\crudkit-apidoc-user" "..\..\..\..\public\crudkit-apidoc-user\" /c /q /e /h /y
)