<#
.SYNOPSIS
Save a YouCam API key privately on Windows without enabling live generation.
.DESCRIPTION
Run from the project root:
  powershell.exe -NoProfile -File .\tools\configure-provider.ps1

Choose the official API host confirmed by your own YouCam console, then enter
the key at the hidden SecureString prompt. Do not put keys in command arguments,
chat, shell history, environment setup commands, or screenshots. This script
does not accept parameters, contact any API, enable live generation, or buy units.

Existing config/local.php requires an explicit REPLACE confirmation. The saved
PHP file returns base64-encoded JSON; base64 is encoding, not encryption. Its
directory and file ACLs are restricted to the current Windows user. Production
credentials must be configured separately for the production service account.

After success, run the read-only check yourself:
  php tools/check-provider.php
The application remains live=false until its separate configuration is changed.
#>
param()

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version 2.0

# No argument can become a credential or be interpolated into diagnostics.
if ($args.Count -ne 0) {
    Write-Host 'No arguments are accepted. Run this script and use its hidden prompt.'
    exit 1
}

$secureKey = $null
$keyPointer = [IntPtr]::Zero
$plainKey = $null
$json = $null
$jsonBytes = $null
$encodedJson = $null
$phpContent = $null
$configValues = $null
$tempPath = $null
$configPath = $null
$configDirectory = $null
$failed = $false
$cancelled = $false
$saved = $false

function Set-PrivateDirectoryAcl {
    param([string] $Path, [System.Security.Principal.SecurityIdentifier] $Sid)
    $acl = New-Object System.Security.AccessControl.DirectorySecurity
    $acl.SetAccessRuleProtection($true, $false)
    $acl.SetOwner($Sid)
    $inherit = [System.Security.AccessControl.InheritanceFlags]::ContainerInherit -bor
        [System.Security.AccessControl.InheritanceFlags]::ObjectInherit
    $rule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $Sid,
        [System.Security.AccessControl.FileSystemRights]::FullControl,
        $inherit,
        [System.Security.AccessControl.PropagationFlags]::None,
        [System.Security.AccessControl.AccessControlType]::Allow
    )
    $acl.AddAccessRule($rule)
    Set-Acl -LiteralPath $Path -AclObject $acl
}

function Set-PrivateFileAcl {
    param([string] $Path, [System.Security.Principal.SecurityIdentifier] $Sid)
    $acl = New-Object System.Security.AccessControl.FileSecurity
    $acl.SetAccessRuleProtection($true, $false)
    $acl.SetOwner($Sid)
    $rule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $Sid,
        [System.Security.AccessControl.FileSystemRights]::FullControl,
        [System.Security.AccessControl.AccessControlType]::Allow
    )
    $acl.AddAccessRule($rule)
    Set-Acl -LiteralPath $Path -AclObject $acl
}

function Test-PlainPath {
    param([string] $Path)
    $entry = Get-Item -LiteralPath $Path -Force
    if (($entry.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
        throw 'Private configuration paths must not be links.'
    }
}

try {
    if ([Environment]::OSVersion.Platform -ne [PlatformID]::Win32NT) {
        throw 'Windows SecureString input and Windows ACLs are required.'
    }
    if (-not (Get-Command Read-Host).Parameters.ContainsKey('AsSecureString')) {
        throw 'Secure input is unavailable.'
    }
    foreach ($target in @('Process', 'User', 'Machine')) {
        $liveOverride = [Environment]::GetEnvironmentVariable('YOUCAM_LIVE_ENABLED', $target)
        if ($null -ne $liveOverride -and @('true', '1', 'yes', 'on') -contains $liveOverride.Trim().ToLowerInvariant()) {
            throw 'An environment override can enable live generation.'
        }
    }

    $projectDirectory = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
    $configDirectory = [IO.Path]::GetFullPath((Join-Path $projectDirectory 'config'))
    $configPath = [IO.Path]::GetFullPath((Join-Path $configDirectory 'local.php'))
    $expectedDirectory = $projectDirectory.TrimEnd('\') + '\config'
    if (-not $configDirectory.Equals($expectedDirectory, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Unexpected configuration directory.'
    }
    Test-PlainPath $projectDirectory
    Test-PlainPath $PSScriptRoot

    $existing = Test-Path -LiteralPath $configPath
    $existingHash = $null
    if (Test-Path -LiteralPath $configDirectory) {
        Test-PlainPath $configDirectory
        if (-not (Get-Item -LiteralPath $configDirectory -Force).PSIsContainer) {
            throw 'Configuration directory is not a directory.'
        }
    }
    if ($existing) {
        Test-PlainPath $configPath
        if ((Get-Item -LiteralPath $configPath -Force).PSIsContainer) {
            throw 'Configuration file is not a file.'
        }
        $confirmation = Read-Host 'Private configuration already exists. Type REPLACE to replace it, or Enter to cancel'
        if ($confirmation -cne 'REPLACE') {
            $cancelled = $true
        } else {
            $existingHash = (Get-FileHash -LiteralPath $configPath -Algorithm SHA256).Hash
        }
    }

    if (-not $cancelled) {
        Write-Host 'Choose the API host confirmed by your official YouCam console:'
        Write-Host '1: https://yce-api-01.perfectcorp.com'
        Write-Host '2: https://yce-api-01.makeupar.com'
        $selection = Read-Host 'Host (1 or 2; Enter cancels)'
        if ($selection -eq '1') {
            $apiHost = 'https://yce-api-01.perfectcorp.com'
        } elseif ($selection -eq '2') {
            $apiHost = 'https://yce-api-01.makeupar.com'
        } elseif ([string]::IsNullOrEmpty($selection)) {
            $cancelled = $true
        } else {
            throw 'An official host must be selected.'
        }
    }

    if (-not $cancelled) {
        # Fail before collecting the secret if private storage cannot be secured.
        $currentSid = [Security.Principal.WindowsIdentity]::GetCurrent().User
        if (-not (Test-Path -LiteralPath $configDirectory)) {
            $null = New-Item -ItemType Directory -Path $configDirectory
        }
        Test-PlainPath $configDirectory
        Set-PrivateDirectoryAcl $configDirectory $currentSid
        if ($existing) {
            Test-PlainPath $configPath
            Set-PrivateFileAcl $configPath $currentSid
        }

        $secureKey = Read-Host 'YouCam API key (hidden)' -AsSecureString
        if ($secureKey.Length -lt 1 -or $secureKey.Length -gt 4096) {
            throw 'The key length is invalid.'
        }
        $keyPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureKey)
        $plainKey = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($keyPointer)
        if ($plainKey.IndexOfAny([char[]]@([char]0, [char]10, [char]13)) -ge 0 -or
            [string]::IsNullOrWhiteSpace($plainKey)) {
            throw 'The key contains invalid characters.'
        }

        $configValues = [ordered]@{
            YOUCAM_API_KEY = $plainKey
            YOUCAM_API_BASE_URL = $apiHost
            YOUCAM_LIVE_ENABLED = $false
        }
        $json = ConvertTo-Json -InputObject $configValues -Compress
        $jsonBytes = [Text.Encoding]::UTF8.GetBytes($json)
        $encodedJson = [Convert]::ToBase64String($jsonBytes)
        $phpContent = "<?php`ndeclare(strict_types=1);`n// Private credentials: base64 is encoding, not encryption.`nreturn json_decode(base64_decode('" +
            $encodedJson + "', true), true, 512, JSON_THROW_ON_ERROR);`n"

        # A protected, unpredictable temporary file avoids partially written config.
        $tempPath = Join-Path $configDirectory ('.local-' + [Guid]::NewGuid().ToString('N') + '.tmp')
        $stream = $null
        $writer = $null
        try {
            $stream = [IO.File]::Open($tempPath, [IO.FileMode]::CreateNew,
                [IO.FileAccess]::Write, [IO.FileShare]::None)
            $writer = New-Object IO.StreamWriter($stream, (New-Object Text.UTF8Encoding($false)))
            $writer.Write($phpContent)
            $writer.Flush()
            $stream.Flush($true)
        } finally {
            if ($null -ne $writer) { $writer.Dispose() }
            elseif ($null -ne $stream) { $stream.Dispose() }
        }
        Set-PrivateFileAcl $tempPath $currentSid
        Test-PlainPath $configDirectory
        Test-PlainPath $tempPath

        if ($existing) {
            if (-not (Test-Path -LiteralPath $configPath)) {
                throw 'Configuration changed while editing.'
            }
            Test-PlainPath $configPath
            $currentHash = (Get-FileHash -LiteralPath $configPath -Algorithm SHA256).Hash
            if ($currentHash -cne $existingHash) {
                throw 'Configuration changed while editing.'
            }
            [IO.File]::Replace($tempPath, $configPath, $null)
        } else {
            # Move without overwrite fails safely if another process created it.
            [IO.File]::Move($tempPath, $configPath)
        }
        $tempPath = $null
        Set-PrivateFileAcl $configPath $currentSid
        $saved = $true
    }
} catch {
    # Never print exception objects, invocation data, config contents or secrets.
    $failed = $true
} finally {
    if ($keyPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($keyPointer)
        $keyPointer = [IntPtr]::Zero
    }
    if ($null -ne $secureKey) { $secureKey.Dispose(); $secureKey = $null }
    if ($null -ne $jsonBytes) { [Array]::Clear($jsonBytes, 0, $jsonBytes.Length) }
    if ($null -ne $configValues) { $configValues.Clear() }
    $plainKey = $null
    $json = $null
    $jsonBytes = $null
    $encodedJson = $null
    $phpContent = $null
    $configValues = $null
    # .NET immutable string copies are not guaranteed erasable; none are logged.
    if ($null -ne $tempPath -and $null -ne $configDirectory) {
        try {
            $checkedTemp = [IO.Path]::GetFullPath($tempPath)
            if ($checkedTemp.StartsWith($configDirectory.TrimEnd('\') + '\', [StringComparison]::OrdinalIgnoreCase) -and
                (Test-Path -LiteralPath $checkedTemp)) {
                Test-PlainPath $checkedTemp
                Remove-Item -LiteralPath $checkedTemp -Force
            }
        } catch {
            $failed = $true
        }
    }
}

if ($failed) {
    Write-Host 'Configuration was not completed. No API was called; live was not enabled. Check local permissions and try again.'
    exit 1
}
if ($cancelled) {
    Write-Host 'Cancelled. Configuration was not replaced.'
    exit 0
}
if ($saved) {
    Write-Host 'Saved. Live remains false.'
    Write-Host 'Next read-only check: php tools/check-provider.php'
    exit 0
}
exit 1
