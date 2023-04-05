# CONFIG -------------------------------------------------------------------------------------------------------
cd $PSScriptRoot
$version = "v1.0.0"
$phpDocumentorVersion = "2.9.1";

$publicDir = "..\..\..\..\public";
$srcDir = "..\src";

$userCss = ".\user.css";
$devCss = ".\dev.css";

$devCssTpl = ".\crudkit-apidoc-dev\css\template.css";
$userCssTpl = ".\crudkit-apidoc-user\css\template.css";

$userDir = ".\crudkit-apidoc-user";
$devDir = ".\crudkit-apidoc-dev";

Write-Host "Building documentation for CRUDKit $version"

$ErrorActionPreference = Continue;

#User Doc -------------------------------------------------------------------------------------------------------
$ignoreuser = "examples/,views/,config/,SQLColumn.php,SQLManyToOneColumn.php,SQLOneToManyColumn.php,EnumType.php,ExceptionHandler.php,Filter.php,XmlSerializer.php,CrudkitController.php,Section.php" 
Remove-Item -Path $userDir -Recurse -Force -ErrorAction SilentlyContinue;
Remove-Item -Path phpdoc-log-user.txt -ErrorAction SilentlyContinue
Remove-Item -Path phpdoc-err-user.txt -ErrorAction SilentlyContinue;
Remove-Item -Path created_with_phpDocumentor_v2.9.1.txt -ErrorAction SilentlyContinue;

php phpDocumentor.phar -d "D:\need absolute shite path here!" -t "./usr" --title "CRUDKit"

exit;
#Developer Doc -------------------------------------------------------------------------------------------------------
$ignoredev="examples/,views/,config/,XmlSerializer.php" 
Remove-Item -Path $devDir -Recurse -Force -ErrorAction SilentlyContinue;
php -d error_reporting=-1 phpDocumentor.phar -d $srcDir -t $devDir --parseprivate --ignore $ignoredev --title "CRUDKit Developer API Doc $version" --sourcecode > .\phpdoc-log-dev.txt 2> .\phpdoc-err-dev.txt

#Change css -------------------------------------------------------------------------------------------------------
if(Test-Path -Path $devCss)
{
    $css = Get-Content -Path $devCss -Encoding UTF8;
    Add-Content -Path $devCssTpl -Value $css -Encoding UTF8;
}

if(Test-Path -Path $userCss)
{
    $css = Get-Content -Path $userCss -Encoding UTF8;
    Add-Content -Path $userCssTpl -Value $css -Encoding UTF8;
}

#Copy to /public -------------------------------------------------------------------------------------------------------
if([System.IO.Directory]::Exists($publicDir))
{	
    
	Remove-Item -Path "..\..\..\..\public\crudkit-apidoc-dev" -Recurse -Force
	Remove-Item -Path "..\..\..\..\public\crudkit-apidoc-user" -Recurse -Force
	xcopy $devDir "..\..\..\..\public\crudkit-apidoc-dev\" /c /q /e /h /y
	xcopy $userDir "..\..\..\..\public\crudkit-apidoc-user\" /c /q /e /h /y
}

New-Item -Path (".\created_with_phpDocumentor_v" + $phpDocumentorVersion + ".txt") -Force | Out-Null;

Write-Host "ApiDoc created" -ForegroundColor Green;