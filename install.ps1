$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$bhPhp = 'C:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $bhPhp)) { throw 'PHP was not found at C:\xampp\php\php.exe. Run setup.php with your PHP executable instead.' }
Write-Host 'BorrowHub standalone setup. Start MySQL in XAMPP before continuing.'
$env:BH_ADMIN_EMAIL = Read-Host 'Administrator email'
$bhSecret = Read-Host 'Administrator password (10-72 bytes)' -AsSecureString
$env:BH_ADMIN_PASSWORD = ([System.Net.NetworkCredential]::new('', $bhSecret)).Password
$bhDemo = Read-Host 'Add clearly labelled demo items and two demo members? (y/N)'
try {
    if ($bhDemo -eq 'y') {
        $bhDemoSecret = Read-Host 'Demo member password (10-72 bytes)' -AsSecureString
        $env:BH_DEMO_PASSWORD = ([System.Net.NetworkCredential]::new('', $bhDemoSecret)).Password
        & $bhPhp setup.php --demo
    } else { & $bhPhp setup.php }
    if ($LASTEXITCODE -ne 0) { throw 'Setup did not complete. Check the database settings and MySQL status.' }
    Write-Host 'Open http://localhost/borrowhub/ after starting Apache.'
    if ($bhDemo -eq 'y') { Write-Host 'Demo members: nimal@example.test and amaya@example.test (with the demo password you chose).' }
} finally {
    Remove-Item Env:BH_ADMIN_EMAIL, Env:BH_ADMIN_PASSWORD, Env:BH_DEMO_PASSWORD -ErrorAction SilentlyContinue
    Remove-Variable bhSecret, bhDemoSecret -ErrorAction SilentlyContinue
}
