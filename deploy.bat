@echo off
chcp 65001 >nul
echo ========================================
echo   Deploy Automático - VemComer Core
echo ========================================
echo.

REM Configurações (AJUSTE AQUI)
set "WP_PATH=D:\xampp\htdocs\vemcomer"
set "PLUGIN_PATH=%WP_PATH%\wp-content\plugins\vemcomer-core"
set "THEME_PATH=%WP_PATH%\wp-content\themes\theme-vemcomer"

REM Verificar se os caminhos existem
if not exist "%WP_PATH%" (
    echo ERRO: Caminho do WordPress não encontrado: %WP_PATH%
    echo.
    echo Por favor, edite o arquivo deploy.bat e ajuste a variável WP_PATH
    echo.
    pause
    exit /b 1
)

echo Verificando estrutura...
echo WordPress: %WP_PATH%
echo Plugin: %PLUGIN_PATH%
echo Tema: %THEME_PATH%
echo.

REM Criar diretórios se não existirem
if not exist "%PLUGIN_PATH%" (
    echo Criando diretório do plugin...
    mkdir "%PLUGIN_PATH%"
)

if not exist "%THEME_PATH%" (
    echo Criando diretório do tema...
    mkdir "%THEME_PATH%"
)

echo.
echo ========================================
echo   1. Copiando arquivos do plugin...
echo ========================================
echo.

REM Copiar arquivos do plugin (excluindo node_modules, .git, etc)
xcopy /E /I /Y /EXCLUDE:deploy-exclude.txt "inc" "%PLUGIN_PATH%\inc"
xcopy /E /I /Y /EXCLUDE:deploy-exclude.txt "assets" "%PLUGIN_PATH%\assets"
xcopy /E /I /Y /EXCLUDE:deploy-exclude.txt "templates" "%PLUGIN_PATH%\templates"
copy /Y "vemcomer-core.php" "%PLUGIN_PATH%\"
copy /Y "uninstall.php" "%PLUGIN_PATH%\"
copy /Y "README.md" "%PLUGIN_PATH%\readme.txt"

echo.
echo ========================================
echo   2. Copiando arquivos do tema...
echo ========================================
echo.

REM Copiar arquivos do tema
xcopy /E /I /Y /EXCLUDE:deploy-exclude.txt "theme-vemcomer\*" "%THEME_PATH%\"

echo.
echo ========================================
echo   ✓ Deploy concluído!
echo ========================================
echo.
echo Arquivos copiados para:
echo - Plugin: %PLUGIN_PATH%
echo - Tema: %THEME_PATH%
echo.
echo Próximos passos:
echo 1. Ative o plugin no WordPress (se não estiver ativo)
echo 2. Ative o tema no WordPress (se não estiver ativo)
echo 3. Limpe o cache do WordPress (se usar plugin de cache)
echo 4. Acesse o site para testar
echo.
pause


