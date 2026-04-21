#ifndef MyAppVersion
#define MyAppVersion "dev"
#endif

#define MyAppName "Ready To Pict Desktop"
#define MyAppPublisher "Ready To Pict"
#define MyAppExeName "desktop_flutter.exe"
#define MyAppId "{{B53D5236-4A96-49BF-BFA7-D9A1762A7DF5}}"

[Setup]
AppId={#MyAppId}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\Ready To Pict Desktop
DefaultGroupName=Ready To Pict Desktop
OutputDir=..\release\installer
OutputBaseFilename=ready_to_pict_desktop_{#MyAppVersion}_setup
Compression=lzma
SolidCompression=yes
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
WizardStyle=modern
PrivilegesRequired=lowest

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "Create a desktop icon"; GroupDescription: "Additional icons:"; Flags: unchecked

[Files]
Source: "..\build\windows\x64\runner\Release\desktop_flutter.exe"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\Ready To Pict Desktop"; Filename: "{app}\{#MyAppExeName}"
Name: "{autodesktop}\Ready To Pict Desktop"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "Launch Ready To Pict Desktop"; Flags: nowait postinstall skipifsilent
