<#
.SYNOPSIS
  Set up Firewall settings for VcXsrv and Selenium
#>
Param(
  [Parameter(Mandatory=$true)]
  [string]$Connection,

  [int]$VcXsrvPort = 6000,
  [int]$SeleniumPort = 4444
)

$seleniumName = 'Totara.Selenium.{84688777-D9EA-4F29-B4A5-C25C49B6C1C3}'
$seleniumDisplayName = 'Selenium webdriver'
$vcxsrvName = 'Totara.VcXsrv.{85D968BE-D7EE-44FA-900E-1EEC8E165256}'
$vcxsrvDisplayName = 'VcXsrv windows xserver'

<#######################
 #                     #
 #   RESTRICTED AREA   #
 #                     #
 #######################>

# VcXsrv
$vcxsrvExe = "$env:ProgramFiles\vcxsrv\vcxsrv.exe"
$ip = '172.23.112.0/20' # or '172.16.0.0/12' ???
if (Test-Path $vcxsrvExe) {
  Remove-NetFirewallRule -Name $vcxsrvName -ErrorAction Ignore
  New-NetFirewallRule -Name $vcxsrvName -DisplayName $vcxsrvDisplayName -Action Allow -Profile Private -Direction Inbound -Program $vcxsrvExe -Protocol TCP -LocalPort $VcXsrvPort -RemoteAddress @($ip) | Out-Null
  Write-Host -n -f Green 'VcXsrv has been set up; Start VcXsrv with '
  Write-Host -f Magenta -b Black 'Disable access control'
} else {
  Write-Host -f Yellow 'VcXsrv was not found'
}

# Selenium
$ip = (Get-NetIPAddress -InterfaceAlias $Connection).IPAddress | ?{$_ -match '^\d+\.\d+\.\d+\.\d+$'}
if ($ip) {
  Remove-NetFirewallRule -Name $seleniumName -ErrorAction Ignore
  New-NetFirewallRule -Name $seleniumName -DisplayName $seleniumDisplayName -Action Allow -Profile Private -Direction Inbound -Protocol TCP -LocalPort $SeleniumPort -RemoteAddress @($ip) | Out-Null
  Write-Host -n -f Green 'Selenium has been set up; Use '
  Write-Host -n -f Cyan -b Black ($ip + ':' + $SeleniumPort)
  Write-Host -f Green ' as host'
} else {
  Write-Host -f Yellow 'Selenium was not set up due to an invalid IP address'
}
