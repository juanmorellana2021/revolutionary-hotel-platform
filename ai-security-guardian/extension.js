const vscode = require('vscode');
const SecurityScanner = require('./lib/security-scanner');
const AutoFixer = require('./lib/auto-fixer');
const LicenseValidator = require('./lib/license-validator');

let securityScanner;
let autoFixer;
let licenseValidator;
let diagnosticCollection;

/**
 * Activates the extension
 */
function activate(context) {
    console.log('AI Security Guardian is now active!');

    // Initialize services
    securityScanner = new SecurityScanner();
    autoFixer = new AutoFixer();
    licenseValidator = new LicenseValidator(context);
    
    // Create diagnostic collection for showing errors in editor
    diagnosticCollection = vscode.languages.createDiagnosticCollection('aiSecurityGuardian');
    context.subscriptions.push(diagnosticCollection);

    // ==================== COMMAND: Scan Current File ====================
    let scanFileCommand = vscode.commands.registerCommand('aiSecurityGuardian.scanFile', async () => {
        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showWarningMessage('No file is currently open');
            return;
        }

        const document = editor.document;
        const fileContent = document.getText();
        const filePath = document.fileName;

        // Show progress
        await vscode.window.withProgress({
            location: vscode.ProgressLocation.Notification,
            title: "Scanning for vulnerabilities...",
            cancellable: false
        }, async (progress) => {
            progress.report({ increment: 0 });

            // Perform scan
            const vulnerabilities = securityScanner.scan(fileContent, filePath);
            
            progress.report({ increment: 100 });

            // Show results
            if (vulnerabilities.length === 0) {
                vscode.window.showInformationMessage('✅ No security issues found!');
                diagnosticCollection.clear();
            } else {
                const highSeverity = vulnerabilities.filter(v => v.severity === 'HIGH' || v.severity === 'CRITICAL').length;
                
                vscode.window.showWarningMessage(
                    `⚠️ Found ${vulnerabilities.length} security issues (${highSeverity} critical)`,
                    'View Issues',
                    'Auto-Fix'
                ).then(async selection => {
                    if (selection === 'View Issues') {
                        showVulnerabilitiesPanel(vulnerabilities, document);
                    } else if (selection === 'Auto-Fix') {
                        vscode.commands.executeCommand('aiSecurityGuardian.autoFix');
                    }
                });

                // Add diagnostics to editor
                addDiagnostics(document, vulnerabilities);
            }
        });
    });

    // ==================== COMMAND: Scan Workspace ====================
    let scanWorkspaceCommand = vscode.commands.registerCommand('aiSecurityGuardian.scanWorkspace', async () => {
        const tier = await licenseValidator.checkLicense();
        
        if (tier === 'free') {
            vscode.window.showInformationMessage(
                'Workspace scanning is a Pro feature. Upgrade for unlimited scans!',
                'Upgrade to Pro'
            ).then(selection => {
                if (selection === 'Upgrade to Pro') {
                    vscode.env.openExternal(vscode.Uri.parse('https://securityai.dev/pricing'));
                }
            });
            return;
        }

        // Scan all JavaScript and PHP files
        const files = await vscode.workspace.findFiles('**/*.{js,php,ts}', '**/node_modules/**');
        
        let totalVulnerabilities = 0;
        let criticalCount = 0;

        await vscode.window.withProgress({
            location: vscode.ProgressLocation.Notification,
            title: `Scanning ${files.length} files...`,
            cancellable: false
        }, async (progress) => {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const document = await vscode.workspace.openTextDocument(file);
                const vulnerabilities = securityScanner.scan(document.getText(), file.fsPath);
                
                totalVulnerabilities += vulnerabilities.length;
                criticalCount += vulnerabilities.filter(v => v.severity === 'CRITICAL').length;
                
                if (vulnerabilities.length > 0) {
                    addDiagnostics(document, vulnerabilities);
                }
                
                progress.report({ 
                    increment: (100 / files.length),
                    message: `${i + 1}/${files.length} files`
                });
            }
        });

        vscode.window.showInformationMessage(
            `Scan complete! Found ${totalVulnerabilities} issues (${criticalCount} critical)`,
            'View Problems'
        ).then(selection => {
            if (selection === 'View Problems') {
                vscode.commands.executeCommand('workbench.actions.view.problems');
            }
        });
    });

    // ==================== COMMAND: Auto-Fix ====================
    let autoFixCommand = vscode.commands.registerCommand('aiSecurityGuardian.autoFix', async () => {
        const tier = await licenseValidator.checkLicense();
        
        if (tier === 'free') {
            vscode.window.showInformationMessage(
                '🔒 Auto-fix is a Pro feature. Upgrade for $9/month to fix issues automatically!',
                'Upgrade to Pro',
                'Enter License Key'
            ).then(async selection => {
                if (selection === 'Upgrade to Pro') {
                    vscode.env.openExternal(vscode.Uri.parse('https://securityai.dev/pricing'));
                } else if (selection === 'Enter License Key') {
                    const key = await vscode.window.showInputBox({
                        prompt: 'Enter your Pro license key',
                        password: true
                    });
                    if (key) {
                        await context.secrets.store('licenseKey', key);
                        vscode.window.showInformationMessage('License key saved! Try again.');
                    }
                }
            });
            return;
        }

        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showWarningMessage('No file is currently open');
            return;
        }

        const document = editor.document;
        const fileContent = document.getText();
        const filePath = document.fileName;

        // Scan for issues
        const vulnerabilities = securityScanner.scan(fileContent, filePath);
        
        if (vulnerabilities.length === 0) {
            vscode.window.showInformationMessage('No security issues to fix!');
            return;
        }

        // Generate fixed version
        const fixedCode = await autoFixer.generateFixedVersion(fileContent, filePath, vulnerabilities);
        
        // Show diff in new editor
        const originalUri = vscode.Uri.parse(`untitled:${filePath}.original`);
        const fixedUri = vscode.Uri.parse(`untitled:${filePath}.fixed`);
        
        await vscode.workspace.openTextDocument(originalUri).then(doc => {
            return vscode.workspace.applyEdit(new vscode.WorkspaceEdit().set(doc.uri, [
                new vscode.TextEdit(new vscode.Range(0, 0, doc.lineCount, 0), fileContent)
            ]));
        });
        
        await vscode.workspace.openTextDocument(fixedUri).then(doc => {
            return vscode.workspace.applyEdit(new vscode.WorkspaceEdit().set(doc.uri, [
                new vscode.TextEdit(new vscode.Range(0, 0, doc.lineCount, 0), fixedCode)
            ]));
        });

        // Show diff
        vscode.commands.executeCommand('vscode.diff', originalUri, fixedUri, 'Security Fixes (Before ← → After)');
        
        vscode.window.showInformationMessage(
            `✅ Fixed ${vulnerabilities.length} security issues!`,
            'Apply Changes'
        ).then(selection => {
            if (selection === 'Apply Changes') {
                const edit = new vscode.WorkspaceEdit();
                edit.replace(
                    document.uri,
                    new vscode.Range(0, 0, document.lineCount, 0),
                    fixedCode
                );
                vscode.workspace.applyEdit(edit);
                vscode.window.showInformationMessage('🎉 Security fixes applied!');
            }
        });
    });

    // ==================== COMMAND: Generate Tests ====================
    let generateTestsCommand = vscode.commands.registerCommand('aiSecurityGuardian.generateTests', async () => {
        const tier = await licenseValidator.checkLicense();
        
        if (tier === 'free') {
            vscode.window.showInformationMessage(
                '🔒 Test generation is a Pro feature. Upgrade for $9/month!',
                'Upgrade to Pro'
            ).then(selection => {
                if (selection === 'Upgrade to Pro') {
                    vscode.env.openExternal(vscode.Uri.parse('https://securityai.dev/pricing'));
                }
            });
            return;
        }

        vscode.window.showInformationMessage('🚧 Test generation coming in v1.1!');
    });

    // ==================== COMMAND: Show Dashboard ====================
    let showDashboardCommand = vscode.commands.registerCommand('aiSecurityGuardian.showDashboard', () => {
        const panel = vscode.window.createWebviewPanel(
            'securityDashboard',
            'Security Dashboard',
            vscode.ViewColumn.One,
            { enableScripts: true }
        );

        panel.webview.html = getDashboardHTML();
    });

    // ==================== AUTO-SCAN ON SAVE ====================
    let autoScanDisposable = vscode.workspace.onDidSaveTextDocument(async (document) => {
        const config = vscode.workspace.getConfiguration('aiSecurityGuardian');
        if (config.get('autoScanOnSave')) {
            const vulnerabilities = securityScanner.scan(document.getText(), document.fileName);
            if (vulnerabilities.length > 0) {
                addDiagnostics(document, vulnerabilities);
            }
        }
    });

    // Register all commands
    context.subscriptions.push(
        scanFileCommand,
        scanWorkspaceCommand,
        autoFixCommand,
        generateTestsCommand,
        showDashboardCommand,
        autoScanDisposable
    );
}

/**
 * Add diagnostics to editor (red squiggly lines)
 */
function addDiagnostics(document, vulnerabilities) {
    const diagnostics = vulnerabilities.map(vuln => {
        const line = Math.max(0, vuln.line - 1); // 0-indexed
        const range = document.lineAt(line).range;
        
        let severity;
        switch (vuln.severity) {
            case 'CRITICAL':
            case 'HIGH':
                severity = vscode.DiagnosticSeverity.Error;
                break;
            case 'MEDIUM':
                severity = vscode.DiagnosticSeverity.Warning;
                break;
            default:
                severity = vscode.DiagnosticSeverity.Information;
        }

        return new vscode.Diagnostic(
            range,
            `[${vuln.severity}] ${vuln.message}`,
            severity
        );
    });

    diagnosticCollection.set(document.uri, diagnostics);
}

/**
 * Show vulnerabilities in panel
 */
function showVulnerabilitiesPanel(vulnerabilities, document) {
    const panel = vscode.window.createWebviewPanel(
        'vulnerabilities',
        'Security Issues',
        vscode.ViewColumn.Beside,
        { enableScripts: true }
    );

    panel.webview.html = getVulnerabilitiesHTML(vulnerabilities, document.fileName);
}

/**
 * Generate HTML for vulnerabilities panel
 */
function getVulnerabilitiesHTML(vulnerabilities, fileName) {
    const items = vulnerabilities.map(v => `
        <div class="vulnerability ${v.severity.toLowerCase()}">
            <div class="header">
                <span class="severity">${v.severity}</span>
                <span class="type">${v.type}</span>
                <span class="line">Line ${v.line}</span>
            </div>
            <div class="message">${v.message}</div>
            ${v.fix ? `<div class="fix">💡 Fix: ${v.fix}</div>` : ''}
        </div>
    `).join('');

    return `<!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; }
            h1 { font-size: 20px; margin-bottom: 20px; }
            .vulnerability { border-left: 4px solid; padding: 12px; margin-bottom: 12px; background: #f5f5f5; }
            .vulnerability.critical { border-color: #d32f2f; }
            .vulnerability.high { border-color: #f57c00; }
            .vulnerability.medium { border-color: #fbc02d; }
            .vulnerability.low { border-color: #388e3c; }
            .header { display: flex; gap: 12px; margin-bottom: 8px; }
            .severity { font-weight: bold; text-transform: uppercase; }
            .type { color: #666; }
            .line { color: #666; font-size: 12px; }
            .message { margin-bottom: 8px; }
            .fix { font-size: 12px; color: #1976d2; margin-top: 8px; }
        </style>
    </head>
    <body>
        <h1>🛡️ Security Issues in ${fileName}</h1>
        ${items}
    </body>
    </html>`;
}

/**
 * Generate HTML for dashboard
 */
function getDashboardHTML() {
    return `<!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; }
            h1 { font-size: 24px; margin-bottom: 20px; }
            .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 30px; }
            .stat { background: #f5f5f5; padding: 20px; border-radius: 8px; text-align: center; }
            .stat-value { font-size: 36px; font-weight: bold; margin-bottom: 8px; }
            .stat-label { color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <h1>🛡️ AI Security Guardian Dashboard</h1>
        <div class="stats">
            <div class="stat">
                <div class="stat-value">0</div>
                <div class="stat-label">Files Scanned</div>
            </div>
            <div class="stat">
                <div class="stat-value">0</div>
                <div class="stat-label">Issues Found</div>
            </div>
            <div class="stat">
                <div class="stat-value">0</div>
                <div class="stat-label">Issues Fixed</div>
            </div>
        </div>
        <p>Start scanning files to see your security metrics!</p>
    </body>
    </html>`;
}

function deactivate() {}

module.exports = {
    activate,
    deactivate
};
