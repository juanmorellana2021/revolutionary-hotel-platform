const vscode = require('vscode');
const SecurityScanner = require('./lib/security-scanner');
const AutoFixer = require('./lib/auto-fixer');
const LicenseValidator = require('./lib/license-validator');
const ErrorHandlingScanner = require('./lib/error-handling-scanner');
const ArchitectureScanner = require('./lib/architecture-scanner');
const DatabaseScanner = require('./lib/database-scanner');
const PerformanceScanner = require('./lib/performance-scanner');
const APIScanner = require('./lib/api-scanner');

let securityScanner;
let errorHandlingScanner;
let architectureScanner;
let databaseScanner;
let performanceScanner;
let apiScanner;
let autoFixer;
let licenseValidator;
let diagnosticCollection;

/**
 * Activates the extension
 */
function activate(context) {
    console.log('AI Dev Engineer is now active!');

    // Initialize all scanners
    securityScanner = new SecurityScanner();
    errorHandlingScanner = new ErrorHandlingScanner();
    architectureScanner = new ArchitectureScanner();
    databaseScanner = new DatabaseScanner();
    performanceScanner = new PerformanceScanner();
    apiScanner = new APIScanner();
    autoFixer = new AutoFixer();
    licenseValidator = new LicenseValidator(context);
    
    // Create diagnostic collection for showing errors in editor
    diagnosticCollection = vscode.languages.createDiagnosticCollection('aiDevEngineer');
    context.subscriptions.push(diagnosticCollection);

    // AUTO-LOAD CONTEXT: Show welcome message with context on startup
    const config = vscode.workspace.getConfiguration('aiDevEngineer');
    if (config.get('autoShowContextOnStartup', true)) {
        setTimeout(() => {
            showContextWelcome(context);
        }, 2000); // Wait 2 seconds for workspace to fully load
    }

    // ==================== COMMAND: Scan Current File ====================
    let scanFileCommand = vscode.commands.registerCommand('aiDevEngineer.scanFile', async () => {
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
            title: "AI Dev Engineer scanning code...",
            cancellable: false
        }, async (progress) => {
            progress.report({ increment: 0, message: "Security..." });

            // Run ALL scanners
            const securityIssues = securityScanner.scan(fileContent, filePath);
            progress.report({ increment: 20, message: "Error handling..." });
            
            const errorIssues = errorHandlingScanner.scan(fileContent, filePath);
            progress.report({ increment: 40, message: "Architecture..." });
            
            const archIssues = architectureScanner.scan(fileContent, filePath);
            progress.report({ increment: 60, message: "Database..." });
            
            const dbIssues = databaseScanner.scan(fileContent, filePath);
            progress.report({ increment: 80, message: "Performance..." });
            
            const perfIssues = performanceScanner.scan(fileContent, filePath);
            progress.report({ increment: 90, message: "API design..." });
            
            const apiIssues = apiScanner.scan(fileContent, filePath);
            
            // Combine all issues
            const allIssues = [
                ...securityIssues,
                ...errorIssues,
                ...archIssues,
                ...dbIssues,
                ...perfIssues,
                ...apiIssues
            ];
            
            progress.report({ increment: 100 });

            // Show results
            if (allIssues.length === 0) {
                vscode.window.showInformationMessage('✅ No issues found! Your code looks great!');
                diagnosticCollection.clear();
            } else {
                const critical = allIssues.filter(v => v.severity === 'CRITICAL').length;
                const high = allIssues.filter(v => v.severity === 'HIGH').length;
                
                // Group by type
                const byType = {
                    SECURITY: allIssues.filter(i => i.type === 'SECURITY').length,
                    ERROR_HANDLING: allIssues.filter(i => i.type === 'ERROR_HANDLING').length,
                    ARCHITECTURE: allIssues.filter(i => i.type === 'ARCHITECTURE').length,
                    DATABASE: allIssues.filter(i => i.type === 'DATABASE').length,
                    PERFORMANCE: allIssues.filter(i => i.type === 'PERFORMANCE').length,
                    API_DESIGN: allIssues.filter(i => i.type === 'API_DESIGN').length
                };
                
                const summary = Object.entries(byType)
                    .filter(([_, count]) => count > 0)
                    .map(([type, count]) => `${count} ${type.toLowerCase()}`)
                    .join(', ');
                
                vscode.window.showWarningMessage(
                    `⚠️ Found ${allIssues.length} issues: ${summary} (${critical + high} critical/high)`,
                    'View Issues',
                    'Auto-Fix'
                ).then(async selection => {
                    if (selection === 'View Issues') {
                        showVulnerabilitiesPanel(allIssues, document);
                    } else if (selection === 'Auto-Fix') {
                        vscode.commands.executeCommand('aiDevEngineer.autoFix');
                    }
                });

                // Add diagnostics to editor
                addDiagnostics(document, allIssues);
            }
        });
    });

    // ==================== COMMAND: Scan Workspace ====================
    let scanWorkspaceCommand = vscode.commands.registerCommand('aiDevEngineer.scanWorkspace', async () => {
        const tier = await licenseValidator.checkLicense();
        
        if (tier === 'free') {
            vscode.window.showInformationMessage(
                'Workspace scanning is a Pro feature. Upgrade for unlimited scans!',
                'Learn More'
            ).then(selection => {
                if (selection === 'Learn More') {
                    vscode.env.openExternal(vscode.Uri.parse('https://github.com/juanmorellana2021/revolutionary-hotel-platform#ai-security-guardian'));
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
    let autoFixCommand = vscode.commands.registerCommand('aiDevEngineer.autoFix', async () => {
        const tier = await licenseValidator.checkLicense();
        
        if (tier === 'free') {
            vscode.window.showInformationMessage(
                '🔒 Auto-fix is a Pro feature. Coming soon at $9/month!',
                'Enter License Key',
                'Learn More'
            ).then(async selection => {
                if (selection === 'Learn More') {
                    vscode.env.openExternal(vscode.Uri.parse('https://github.com/juanmorellana2021/revolutionary-hotel-platform#ai-security-guardian'));
                } else if (selection === 'Enter License Key') {
                    const key = await vscode.window.showInputBox({
                        prompt: 'Enter your Pro license key',
                        password: true,
                        placeHolder: 'xxxx-xxxx-xxxx-xxxx'
                    });
                    if (key) {
                        await context.secrets.store('licenseKey', key);
                        vscode.window.showInformationMessage('License key saved! Try auto-fix again.');
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
    let generateTestsCommand = vscode.commands.registerCommand('aiDevEngineer.generateTests', async () => {
        const tier = await licenseValidator.checkLicense();
        
        if (tier === 'free') {
            vscode.window.showInformationMessage(
                '🔒 Test generation is a Pro feature. Coming soon!',
                'Learn More'
            ).then(selection => {
                if (selection === 'Learn More') {
                    vscode.env.openExternal(vscode.Uri.parse('https://github.com/juanmorellana2021/revolutionary-hotel-platform#ai-security-guardian'));
                }
            });
            return;
        }

        vscode.window.showInformationMessage('🚧 Test generation coming in v1.1!');
    });

    // ==================== COMMAND: Show Dashboard ====================
    let showDashboardCommand = vscode.commands.registerCommand('aiDevEngineer.showDashboard', () => {
        const panel = vscode.window.createWebviewPanel(
            'securityDashboard',
            'Security Dashboard',
            vscode.ViewColumn.One,
            { enableScripts: true }
        );

        panel.webview.html = getDashboardHTML();
    });

    // ==================== COMMAND: Load Project Context ====================
    let loadContextCommand = vscode.commands.registerCommand('aiDevEngineer.loadContext', async () => {
        // Look for ai-dev-engineer.json in .vscode folder
        const workspaceFolders = vscode.workspace.workspaceFolders;
        
        if (!workspaceFolders) {
            vscode.window.showWarningMessage('No workspace folder open');
            return;
        }

        const contextFilePath = vscode.Uri.joinPath(
            workspaceFolders[0].uri,
            '.vscode',
            'ai-dev-engineer.json'
        );

        let contextData;
        let contextExists = false;

        try {
            const fileContent = await vscode.workspace.fs.readFile(contextFilePath);
            contextData = JSON.parse(fileContent.toString());
            contextExists = true;
        } catch (error) {
            // File doesn't exist - create template
            contextData = {
                projectName: "New Project",
                improvementPlan: {
                    methodology: "Page-by-page systematic improvements",
                    currentPhase: "Initial Setup",
                    completedPages: [],
                    pagesQueue: []
                },
                securityPatterns: {},
                codeStandards: {},
                nextSteps: []
            };
        }

        // Generate context prompt
        const completedPages = contextData.improvementPlan?.completedPages || [];
        const pagesQueue = contextData.improvementPlan?.pagesQueue || [];
        const securityPatterns = Object.keys(contextData.securityPatterns || {}).join(', ') || 'None yet';
        
        const lastCompleted = completedPages.length > 0 
            ? completedPages[completedPages.length - 1].page 
            : 'None';
        
        const nextPage = pagesQueue.length > 0 ? pagesQueue[0] : 'TBD';

        const contextPrompt = `Context: ${contextData.projectName || 'Project'} - AI Dev Engineer Workflow

📋 Project State:
- Methodology: ${contextData.improvementPlan?.methodology || 'Page-by-page improvements'}
- Current Phase: ${contextData.improvementPlan?.currentPhase || 'Setup'}
- Last Completed: ${lastCompleted}
- Next Target: ${nextPage}

✅ Completed Pages (${completedPages.length}):
${completedPages.map(p => `- ${p.page} (${p.date}): ${p.improvements?.slice(0, 2).join(', ') || 'Improvements applied'}`).join('\n') || '- None yet'}

📚 Security Patterns Applied:
${securityPatterns}

🎯 Code Standards:
${Object.entries(contextData.codeStandards || {}).map(([key, val]) => `- ${key}: ${val}`).join('\n') || '- Read from .vscode/ai-dev-engineer.json'}

⏭️ Next Steps:
${contextData.nextSteps?.slice(0, 3).map((step, i) => `${i + 1}. ${step}`).join('\n') || '1. Analyze next page\n2. Apply security patterns\n3. Test thoroughly'}

📄 Full Context: Read .vscode/ai-dev-engineer.json for complete details

---
Ready to continue! What should we work on?`;

        // Copy to clipboard
        await vscode.env.clipboard.writeText(contextPrompt);

        // Show notification with preview
        const action = await vscode.window.showInformationMessage(
            `✅ Context copied to clipboard! ${contextExists ? 'Based on your ai-dev-engineer.json' : '(Template - customize your JSON file)'}`,
            'Paste in Chat',
            'Edit Context File',
            'View Full Context'
        );

        if (action === 'Edit Context File') {
            if (!contextExists) {
                // Create template file
                const encoder = new TextEncoder();
                await vscode.workspace.fs.writeFile(
                    contextFilePath,
                    encoder.encode(JSON.stringify(contextData, null, 2))
                );
            }
            const doc = await vscode.workspace.openTextDocument(contextFilePath);
            await vscode.window.showTextDocument(doc);
        } else if (action === 'View Full Context') {
            // Show in new untitled file
            const doc = await vscode.workspace.openTextDocument({
                content: contextPrompt,
                language: 'markdown'
            });
            await vscode.window.showTextDocument(doc);
        }
    });

    // ==================== AUTO-SCAN ON SAVE ====================
    let autoScanDisposable = vscode.workspace.onDidSaveTextDocument(async (document) => {
        const config = vscode.workspace.getConfiguration('aiDevEngineer');
        if (config.get('autoScanOnSave')) {
            const vulnerabilities = securityScanner.scan(document.getText(), document.fileName);
            if (vulnerabilities.length > 0) {
                addDiagnostics(document, vulnerabilities);
            }
        }
    });

    // ==================== CHAT PARTICIPANT: Auto-Context ====================
    const chatParticipant = vscode.chat.createChatParticipant('ai-dev-engineer.context', async (request, chatContext, stream, token) => {
        // Load project context
        const workspaceFolders = vscode.workspace.workspaceFolders;
        
        if (!workspaceFolders) {
            stream.markdown('No workspace folder open. Please open a project folder first.');
            return;
        }

        const contextFilePath = vscode.Uri.joinPath(
            workspaceFolders[0].uri,
            '.vscode',
            'ai-dev-engineer.json'
        );

        let contextData;
        try {
            const fileContent = await vscode.workspace.fs.readFile(contextFilePath);
            contextData = JSON.parse(fileContent.toString());
        } catch (error) {
            stream.markdown('⚠️ No context file found. Create `.vscode/ai-dev-engineer.json` to track your project progress.');
            return;
        }

        // Generate context markdown
        const completedPages = contextData.improvementPlan?.completedPages || [];
        const pagesQueue = contextData.improvementPlan?.pagesQueue || [];
        const lastCompleted = completedPages.length > 0 ? completedPages[completedPages.length - 1].page : 'None';
        const nextPage = pagesQueue.length > 0 ? pagesQueue[0] : 'TBD';

        stream.markdown(`## 📋 ${contextData.projectName || 'Project'} Context\n\n`);
        stream.markdown(`**Methodology:** ${contextData.improvementPlan?.methodology || 'Page-by-page improvements'}\n\n`);
        stream.markdown(`**Current Phase:** ${contextData.improvementPlan?.currentPhase || 'Setup'}\n\n`);
        stream.markdown(`**Last Completed:** ${lastCompleted}\n\n`);
        stream.markdown(`**Next Target:** ${nextPage}\n\n`);
        
        if (completedPages.length > 0) {
            stream.markdown(`### ✅ Completed Pages (${completedPages.length}):\n\n`);
            completedPages.slice(-5).forEach(p => {
                stream.markdown(`- **${p.page}** (${p.date}): ${p.improvements?.slice(0, 2).join(', ') || 'Improvements applied'}\n`);
            });
            stream.markdown('\n');
        }

        const securityPatterns = Object.keys(contextData.securityPatterns || {});
        if (securityPatterns.length > 0) {
            stream.markdown(`### 🔒 Security Patterns:\n${securityPatterns.join(', ')}\n\n`);
        }

        if (contextData.nextSteps && contextData.nextSteps.length > 0) {
            stream.markdown(`### ⏭️ Next Steps:\n\n`);
            contextData.nextSteps.slice(0, 3).forEach((step, i) => {
                stream.markdown(`${i + 1}. ${step}\n`);
            });
        }

        stream.markdown('\n\n---\n\n**Ready to continue!** What should we work on?\n');
    });

    chatParticipant.iconPath = new vscode.ThemeIcon('rocket');

    // Register all commands
    context.subscriptions.push(
        scanFileCommand,
        scanWorkspaceCommand,
        autoFixCommand,
        generateTestsCommand,
        showDashboardCommand,
        loadContextCommand,
        autoScanDisposable,
        chatParticipant
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
        <h1>🛡️ AI Dev Engineer Dashboard</h1>
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

/**
 * Show context welcome message on startup
 */
async function showContextWelcome(context) {
    const workspaceFolders = vscode.workspace.workspaceFolders;
    if (!workspaceFolders) return;

    const contextFilePath = vscode.Uri.joinPath(
        workspaceFolders[0].uri,
        '.vscode',
        'ai-dev-engineer.json'
    );

    let contextExists = false;
    try {
        await vscode.workspace.fs.readFile(contextFilePath);
        contextExists = true;
    } catch (error) {
        // File doesn't exist
        return;
    }

    if (contextExists) {
        const action = await vscode.window.showInformationMessage(
            '🚀 AI Dev Engineer: Ready! Context available for new chat sessions.',
            'Copy Context Now',
            'Don\'t Show Again'
        );

        if (action === 'Copy Context Now') {
            vscode.commands.executeCommand('aiDevEngineer.loadContext');
        } else if (action === 'Don\'t Show Again') {
            const config = vscode.workspace.getConfiguration('aiDevEngineer');
            await config.update('autoShowContextOnStartup', false, true);
        }
    }
}

function deactivate() {}

module.exports = {
    activate,
    deactivate
};
