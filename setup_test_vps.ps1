# Setup Test VPS SSH Access - Automated
# This script copies your SSH key to the test VPS using expect-like behavior

$testVpsIp = "212.227.241.193"
$testVpsUser = "root"
$password = "z40PrWOa"
$publicKeyPath = "$env:USERPROFILE\.ssh\id_rsa.pub"

Write-Host "=== Setting up SSH access to Test VPS ===" -ForegroundColor Green
Write-Host "VPS IP: $testVpsIp" -ForegroundColor Cyan
Write-Host ""

# Read the public key
$publicKey = Get-Content $publicKeyPath -Raw
$publicKey = $publicKey.Trim()

Write-Host "Step 1: Installing SSH key on test VPS..." -ForegroundColor Yellow

# Create a command that will be executed on the remote server
$remoteCommand = "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '$publicKey' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys && echo 'SSH key installed successfully!'"

# Try to execute - user will need to enter password manually
Write-Host "Please enter the password when prompted: z40PrWOa" -ForegroundColor Magenta
ssh -o StrictHostKeyChecking=no -o PubkeyAuthentication=no $testVpsUser@$testVpsIp $remoteCommand

if ($LASTEXITCODE -eq 0) {
    Write-Host "`nStep 2: Testing SSH connection without password..." -ForegroundColor Yellow
    ssh $testVpsUser@$testVpsIp "whoami && pwd"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n=== SUCCESS! ===" -ForegroundColor Green
        Write-Host "SSH key-based authentication is now set up!" -ForegroundColor Green
        Write-Host "You can now connect with: ssh root@$testVpsIp" -ForegroundColor Cyan
    }
} else {
    Write-Host "`nSetup failed. Please run these commands manually:" -ForegroundColor Red
    Write-Host "ssh root@$testVpsIp" -ForegroundColor Cyan
    Write-Host "Then on the server run:" -ForegroundColor Cyan
    Write-Host "mkdir -p ~/.ssh && chmod 700 ~/.ssh" -ForegroundColor Yellow
    Write-Host "echo '$publicKey' >> ~/.ssh/authorized_keys" -ForegroundColor Yellow
    Write-Host "chmod 600 ~/.ssh/authorized_keys" -ForegroundColor Yellow
}
