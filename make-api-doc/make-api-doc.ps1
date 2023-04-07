<#
This script builds the api-doc html page by using phpdocumentor 3
#>

# CONFIG -------------------------------------------------------------------------------------------------------
cd $PSScriptRoot;

#Source directory of crudkit
$srcDir = "..\src";

#Config files for phpdoc (needs to be an absolute path)
$userConfig = $PSScriptRoot  + "\phpdoc.user.config.xml";
$devConfig = $PSScriptRoot + "\phpdoc.dev.config.xml";

#phpdoc log log (stdout and errout)
$userErrOut = ".\phpdoc-user-errout.txt";
$userStdOut = ".\phpdoc-user-stdout.txt";
$devErrOut = ".\phpdoc-dev-errout.txt";
$devStdOut = ".\phpdoc-dev-stdout.txt";

#Path to the the output directores (note that these need to be the same as in the config files)
$userDir = "..\api-doc\user";
$devDir = "..\api-doc\dev";

#PREPARE -------------------------------------------------------------------------------------------------------

Remove-Item -LiteralPath $userDir -Recurse -Force -ErrorAction SilentlyContinue;
Remove-Item -LiteralPath $devDir -Recurse -Force -ErrorAction SilentlyContinue;
$userDocOk = $false;
$devDockOk = $false;

#BUILD USER DOC -------------------------------------------------------------------------------------------------------
#phpDocumentor.phar (v3.3.1) is not included in this git repo, because its big and ppl. dont need to download that. 
#Get it from github: https://github.com/phpDocumentor/phpDocumentor
php phpDocumentor.phar --config $userConfig > $userStdOut 2> $userErrOut;

if(Test-Path -LiteralPath ($userDir + '\index.html'))
{
    #teplate.color from phpdoc does not work, so fuck you:
    $css = ":root{--primary-color-hue: 202; --primary-color-saturation: 68%;} .phpdocumentor-table-of-contents__entry::before{background: none !important; background-color: hsl(202,68%,60%) !important;} ";
    Out-File -LiteralPath ($userDir + "\css\base.css") -InputObject $css -Append -Encoding utf8 -ErrorAction SilentlyContinue; 
    
    $userDocOk = $true;
    Write-Host "api-doc [user] successfully created!" -ForegroundColor Green; 
}
else
{ 
    Write-Host "error while creating api-doc [user]. check phpdoc logs." -ForegroundColor Red; 
}


#BUILD DEV DOC -------------------------------------------------------------------------------------------------------
php phpDocumentor.phar --config $devConfig > $devStdOut 2> $devErrOut;

if(Test-Path -LiteralPath ($devDir + '\index.html'))
{
    $css = ":root{--primary-color-hue: 320; --primary-color-saturation: 68%;} .phpdocumentor-table-of-contents__entry::before{background: none !important; background-color: hsl(320,68%,60%) !important;} ";
    Out-File -LiteralPath ($devDir + "\css\base.css") -InputObject $css -Append -Encoding utf8 -ErrorAction SilentlyContinue;
 
    $devDockOk = $true;
    Write-Host "api-doc [dev] successfully created!" -ForegroundColor Green; 
}
else
{ 
    Write-Host "error while creating api-doc [dev]. check phpdoc logs." -ForegroundColor Red; 
}
