param(
    [Parameter(Mandatory = $true)]
    [string]$ApiBaseUrl,
    [Parameter(Mandatory = $true)]
    [string]$Email,
    [Parameter(Mandatory = $true)]
    [string]$Password
)

$ErrorActionPreference = "Stop"

function Invoke-JsonRequest {
    param(
        [string]$Method,
        [string]$Url,
        [hashtable]$Headers,
        [object]$Body,
        [int[]]$AllowedStatus = @(200)
    )

    $params = @{
        Method = $Method
        Uri = $Url
        Headers = $Headers
        ErrorAction = "Stop"
    }

    if ($null -ne $Body) {
        $params.Body = ($Body | ConvertTo-Json -Depth 10)
        $params.ContentType = "application/json"
    }

    try {
        $response = Invoke-WebRequest @params
        $statusCode = [int]$response.StatusCode
        $content = $response.Content
    }
    catch {
        if ($_.Exception.Response -and $_.Exception.Response.StatusCode) {
            $statusCode = [int]$_.Exception.Response.StatusCode
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $content = $reader.ReadToEnd()
            $reader.Close()
        }
        else {
            throw
        }
    }

    if ($AllowedStatus -notcontains $statusCode) {
        throw "Request $Method $Url gagal. Status: $statusCode. Body: $content"
    }

    if ([string]::IsNullOrWhiteSpace($content)) {
        return @{
            status = $statusCode
            data = $null
        }
    }

    return @{
        status = $statusCode
        data = ($content | ConvertFrom-Json)
    }
}

$base = $ApiBaseUrl.TrimEnd('/')
$headers = @{
    "Accept" = "application/json"
}

Write-Host "[1/9] Check public branches"
Invoke-JsonRequest -Method "GET" -Url "$base/branches" -Headers $headers -Body $null | Out-Null

Write-Host "[2/9] Check public packages"
Invoke-JsonRequest -Method "GET" -Url "$base/packages" -Headers $headers -Body $null | Out-Null

Write-Host "[3/9] Login"
$login = Invoke-JsonRequest -Method "POST" -Url "$base/auth/login" -Headers $headers -Body @{
    email = $Email
    password = $Password
    device_name = "windows-smoke-test"
}

$token = $login.data.data.token

if ([string]::IsNullOrWhiteSpace($token)) {
    throw "Token login kosong."
}

$authHeaders = @{
    "Accept" = "application/json"
    "Authorization" = "Bearer $token"
}

$today = Get-Date -Format "yyyy-MM-dd"

Write-Host "[4/9] Profile"
Invoke-JsonRequest -Method "GET" -Url "$base/profile" -Headers $authHeaders -Body $null | Out-Null

Write-Host "[5/9] Bookings"
Invoke-JsonRequest -Method "GET" -Url "$base/bookings?date=$today&per_page=5" -Headers $authHeaders -Body $null | Out-Null

Write-Host "[6/9] Queue tickets"
Invoke-JsonRequest -Method "GET" -Url "$base/queue-tickets?queue_date=$today&per_page=5" -Headers $authHeaders -Body $null | Out-Null

Write-Host "[7/9] Transactions"
Invoke-JsonRequest -Method "GET" -Url "$base/transactions?per_page=5" -Headers $authHeaders -Body $null | Out-Null

Write-Host "[8/9] Reports summary"
Invoke-JsonRequest -Method "GET" -Url "$base/reports/summary?from=$today&to=$today" -Headers $authHeaders -Body $null | Out-Null

Write-Host "[9/9] Logout"
Invoke-JsonRequest -Method "POST" -Url "$base/auth/logout" -Headers $authHeaders -Body @{} -AllowedStatus @(200, 204) | Out-Null

Write-Host "Smoke test selesai: API utama merespons normal."
