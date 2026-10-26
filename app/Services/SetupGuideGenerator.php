<?php

class SetupGuideGenerator {
    public static function generateHtmlGuide(Project $project, ProjectVersion $version, array $requirements, bool $hasDbBackup): string {
        $lang = htmlspecialchars($project->project_type);
        $name = htmlspecialchars($project->project_name);

        $reqListHtml = '';
        foreach ($requirements as $r) {
            $reqListHtml .= sprintf(
                "<li><strong>%s (%s):</strong> %s %s</li>",
                htmlspecialchars($r['requirement_name']),
                htmlspecialchars($r['requirement_type']),
                htmlspecialchars($r['version_constraint'] ?? ''),
                $r['status'] === 'verified' ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-secondary">Inferred</span>'
            );
        }

        $stepsHtml = self::getLanguageStepsHtml($project->project_type, $hasDbBackup);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation & Setup Guide - {$name}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0F172A; color: #F1F5F9; margin: 0; padding: 40px; }
        .container { max-width: 900px; margin: 0 auto; background: #1E293B; border-radius: 12px; padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1, h2, h3 { color: #38BDF8; border-bottom: 1px solid #334155; padding-bottom: 8px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .bg-success { background: #22C55E; color: #fff; }
        .bg-secondary { background: #64748B; color: #fff; }
        .bg-warning { background: #F59E0B; color: #000; }
        ol li { margin-bottom: 12px; line-height: 1.6; }
        code { background: #0F172A; color: #38BDF8; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        pre { background: #0F172A; padding: 16px; border-radius: 8px; overflow-x: auto; color: #A5F3FC; }
        .disclaimer { background: #334155; padding: 16px; border-radius: 8px; font-size: 14px; border-left: 4px solid #F59E0B; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>CloudVault Restoration Guide</h1>
        <p><strong>Project:</strong> {$name} (v{$version->version_number})</p>
        <p><strong>Language / Stack:</strong> {$lang}</p>

        <h2>Detected Requirements</h2>
        <ul>
            {$reqListHtml}
        </ul>

        <h2>Step-by-Step Restoration Instructions</h2>
        <ol>
            {$stepsHtml}
        </ol>

        <div class="disclaimer">
            <strong>Important Compatibility Notice:</strong> CloudVault has preserved all original files and directory structures verified via SHA-256 checksums. However, actual runtime compatibility depends on target computer OS, database software, and local configuration.
        </div>
    </div>
</body>
</html>
HTML;
    }

    public static function generateTxtGuide(Project $project, ProjectVersion $version, array $requirements, bool $hasDbBackup): string {
        $txt = "=========================================================\n";
        $txt .= "  CLOUDVAULT PROJECT RESTORATION GUIDE\n";
        $txt .= "=========================================================\n";
        $txt .= "Project Name : " . $project->project_name . "\n";
        $txt .= "Version      : " . $version->version_number . "\n";
        $txt .= "Language     : " . $project->project_type . "\n";
        $txt .= "Backup Mode  : " . strtoupper($version->backup_mode) . "\n\n";

        $txt .= "DETECTED REQUIREMENTS:\n";
        foreach ($requirements as $r) {
            $txt .= " - " . $r['requirement_name'] . " (" . $r['requirement_type'] . ") " . ($r['version_constraint'] ?? '') . "\n";
        }
        $txt .= "\n";

        $txt .= "RESTORATION INSTRUCTIONS:\n";
        $txt .= strip_tags(self::getLanguageStepsHtml($project->project_type, $hasDbBackup));
        $txt .= "\n\n";
        $txt .= "Notice: Runtime compatibility depends on local computer configuration and installed software.\n";

        return $txt;
    }

    private static function getLanguageStepsHtml(string $language, bool $hasDbBackup): string {
        $dbStep = $hasDbBackup
            ? "<li>Import the database backup file located in <code>database/</code> using phpMyAdmin or MySQL CLI.</li>"
            : "<li>No database backup was included in this backup package. Ensure your local database schema is configured if required.</li>";

        switch (strtoupper($language)) {
            case 'PHP':
                return "
                    <li>Install local web server stack (e.g., XAMPP, WAMP, or Apache + PHP + MySQL).</li>
                    <li>Extract the contents of <code>project/</code> into your web server document root (e.g., <code>htdocs/</code>).</li>
                    {$dbStep}
                    <li>Check database configuration settings in configuration files (e.g., <code>config/database.php</code> or <code>.env</code>) and update host, user, and password.</li>
                    <li>Start Apache & MySQL and navigate to <code>http://localhost/your-project-folder</code>.</li>
                ";
            case 'PYTHON':
                return "
                    <li>Install Python 3.8+ on your system.</li>
                    <li>Open a terminal and navigate to the extracted <code>project/</code> folder.</li>
                    <li>Create a virtual environment: <code>python -m venv venv</code></li>
                    <li>Activate environment: <code>source venv/bin/activate</code> (Linux/Mac) or <code>venv\\Scripts\\activate</code> (Windows).</li>
                    <li>Install dependencies if <code>requirements.txt</code> exists: <code>pip install -r requirements.txt</code></li>
                    {$dbStep}
                    <li>Run application entry script (e.g. <code>python main.py</code> or <code>python manage.py runserver</code>).</li>
                ";
            case 'JAVASCRIPT / NODE.JS':
            case 'JAVASCRIPT':
                return "
                    <li>Install Node.js (v16+) and npm/yarn on your system.</li>
                    <li>Navigate into <code>project/</code> directory.</li>
                    <li>Install dependencies: <code>npm install</code> or <code>yarn install</code></li>
                    {$dbStep}
                    <li>Start project using documented command: <code>npm start</code> or <code>npm run dev</code>.</li>
                ";
            case 'JAVA':
                return "
                    <li>Install JDK 11 or higher and configure <code>JAVA_HOME</code>.</li>
                    <li>If Maven/Gradle wrapper exists, execute build command: <code>./mvnw clean install</code> or <code>./gradlew build</code>.</li>
                    {$dbStep}
                    <li>Run the compiled JAR file or start from your IDE (Eclipse/IntelliJ).</li>
                ";
            case 'C++':
                return "
                    <li>Install C++ compiler (GCC, Clang, or MSVC) and CMake if required.</li>
                    <li>Navigate to <code>project/</code> directory in terminal.</li>
                    <li>Compile project: <code>mkdir build && cd build && cmake .. && make</code></li>
                    <li>Execute the generated executable file.</li>
                ";
            default:
                return "
                    <li>Extract <code>project/</code> files to your preferred workspace.</li>
                    {$dbStep}
                    <li>Follow standard project execution steps for {$language}.</li>
                ";
        }
    }
}
