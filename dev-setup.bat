@echo off
chcp 65001 >nul
echo ========================================
echo   Setup de Desenvolvimento Local
echo ========================================
echo.
echo Este script vai configurar um ambiente
echo de desenvolvimento local para testar
echo as mudanças antes de fazer deploy.
echo.
echo.

REM Verificar se XAMPP está instalado
set "XAMPP_PATH=C:\xampp"
if not exist "%XAMPP_PATH%" (
    set "XAMPP_PATH=D:\xampp"
    if not exist "%XAMPP_PATH%" (
        echo ERRO: XAMPP não encontrado!
        echo.
        echo Por favor, instale o XAMPP ou ajuste
        echo o caminho no arquivo dev-setup.bat
        echo.
        pause
        exit /b 1
    )
)

echo XAMPP encontrado em: %XAMPP_PATH%
echo.

REM Criar estrutura de diretórios
set "WP_PATH=%XAMPP_PATH%\htdocs\vemcomer-dev"
set "PLUGIN_PATH=%WP_PATH%\wp-content\plugins\vemcomer-core"
set "THEME_PATH=%WP_PATH%\wp-content\themes\theme-vemcomer"

echo Criando estrutura em: %WP_PATH%
echo.

if not exist "%WP_PATH%" (
    echo Criando diretório do WordPress...
    mkdir "%WP_PATH%"
)

if not exist "%WP_PATH%\wp-content" (
    mkdir "%WP_PATH%\wp-content"
)

if not exist "%WP_PATH%\wp-content\plugins" (
    mkdir "%WP_PATH%\wp-content\plugins"
)

if not exist "%WP_PATH%\wp-content\themes" (
    mkdir "%WP_PATH%\wp-content\themes"
)

echo.
echo ========================================
echo   Estrutura criada!
echo ========================================
echo.
echo Próximos passos:
echo.
echo 1. Baixe o WordPress em: %WP_PATH%
echo    https://wordpress.org/download/
echo.
echo 2. Configure o banco de dados MySQL
echo    - Crie um banco chamado: vemcomer_dev
echo    - Usuário: root
echo    - Senha: (vazio)
echo.
echo 3. Execute o deploy.bat para copiar os arquivos
echo.
echo 4. Acesse: http://localhost/vemcomer-dev
echo.
pause


