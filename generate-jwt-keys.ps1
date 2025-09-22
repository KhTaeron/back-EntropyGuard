$JWT_DIR = "config\jwt"

if (!(Test-Path -Path $JWT_DIR)) {
    New-Item -ItemType Directory -Force -Path $JWT_DIR | Out-Null
    Write-Output "Dossier $JWT_DIR créé."
}

openssl genpkey `
    -out "$JWT_DIR\private.pem" `
    -aes256 `
    -algorithm rsa `
    -pkeyopt rsa_keygen_bits:4096 `
    -pass pass:$JWT_PASSPHRASE

openssl pkey `
    -in "$JWT_DIR\private.pem" `
    -out "$JWT_DIR\public.pem" `
    -pubout `
    -passin pass:$JWT_PASSPHRASE

