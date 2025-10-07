<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Test - Windows Compatibility</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { border-left-color: #28a745; }
        .warning { border-left-color: #ffc107; }
        .danger { border-left-color: #dc3545; }
        button {
            padding: 10px 15px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        input[type="file"] {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            width: 100%;
            font-size: 14px;
        }
        #log {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>🔧 File Upload Test - Windows Compatibility Check</h1>
    <p>This page tests different file upload methods to identify what's causing the freezing issue.</p>

    <!-- Test 1: Simple HTML File Input -->
    <div class="test-section success">
        <h3>✅ Test 1: Simple HTML File Input (Most Reliable)</h3>
        <input type="file" id="simpleInput" multiple accept="image/*" onchange="testSimpleInput(this)">
        <p><strong>This should work without any freezing on Windows.</strong></p>
    </div>

    <!-- Test 2: JavaScript Triggered Input -->
    <div class="test-section warning">
        <h3>⚠️ Test 2: JavaScript Triggered File Dialog</h3>
        <button class="btn-warning" onclick="testJSInput()">📁 Open File Dialog via JavaScript</button>
        <input type="file" id="hiddenInput" style="display: none;" multiple accept="image/*" onchange="testHiddenInput(this)">
        <p><em>This method sometimes freezes on Windows.</em></p>
    </div>

    <!-- Test 3: Dynamic Input Creation -->
    <div class="test-section danger">
        <h3>🚨 Test 3: Dynamic Input Creation</h3>
        <button class="btn-danger" onclick="testDynamicInput()">🔧 Create & Trigger Dynamic Input</button>
        <p><em>This is the most problematic method on Windows.</em></p>
    </div>

    <!-- Test 4: Drag & Drop -->
    <div class="test-section success">
        <h3>✅ Test 4: Drag & Drop</h3>
        <div id="dropZone" style="border: 2px dashed #28a745; padding: 30px; text-align: center; border-radius: 8px; background: #f8fff8;">
            <p style="margin: 0; font-size: 18px;">📎 Drag & Drop Files Here</p>
        </div>
    </div>

    <!-- Diagnostic Tools -->
    <div class="test-section">
        <h3>🔍 Diagnostic Tools</h3>
        <button class="btn-primary" onclick="runDiagnostics()">🔍 Run System Diagnostics</button>
        <button class="btn-success" onclick="clearLog()">🧹 Clear Log</button>
        <button class="btn-danger" onclick="forceRefresh()">🔄 Force Refresh</button>
    </div>

    <!-- Log Output -->
    <div class="test-section">
        <h3>📋 Test Log</h3>
        <div id="log"></div>
    </div>

    <script>
        // Logging function
        function log(message) {
            const logDiv = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            logDiv.textContent += `[${timestamp}] ${message}\n`;
            logDiv.scrollTop = logDiv.scrollHeight;
            console.log(message);
        }

        // Test 1: Simple HTML file input
        function testSimpleInput(input) {
            if (input.files && input.files.length > 0) {
                log(`✅ SUCCESS: Simple input selected ${input.files.length} file(s)`);
                for (let i = 0; i < input.files.length; i++) {
                    log(`   📄 File ${i+1}: ${input.files[i].name} (${input.files[i].size} bytes)`);
                }
            } else {
                log('📁 Simple input: No files selected');
            }
        }

        // Test 2: JavaScript triggered input
        function testJSInput() {
            log('⚠️ TESTING: JavaScript triggered file dialog...');
            const hiddenInput = document.getElementById('hiddenInput');
            
            try {
                hiddenInput.click();
                log('📁 File dialog triggered successfully');
                
                // Set timeout to detect freeze
                setTimeout(() => {
                    if (!hiddenInput.files || hiddenInput.files.length === 0) {
                        log('⚠️ WARNING: File dialog may have frozen or was cancelled');
                    }
                }, 5000);
                
            } catch (error) {
                log(`❌ ERROR: Failed to trigger file dialog - ${error.message}`);
            }
        }

        function testHiddenInput(input) {
            if (input.files && input.files.length > 0) {
                log(`✅ SUCCESS: JavaScript input selected ${input.files.length} file(s)`);
            } else {
                log('📁 JavaScript input: No files selected or dialog cancelled');
            }
        }

        // Test 3: Dynamic input creation
        function testDynamicInput() {
            log('🚨 TESTING: Dynamic input creation...');
            
            try {
                const dynamicInput = document.createElement('input');
                dynamicInput.type = 'file';
                dynamicInput.multiple = true;
                dynamicInput.accept = 'image/*';
                dynamicInput.style.display = 'none';
                
                dynamicInput.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files.length > 0) {
                        log(`✅ SUCCESS: Dynamic input selected ${e.target.files.length} file(s)`);
                    } else {
                        log('📁 Dynamic input: No files selected');
                    }
                    
                    // Cleanup
                    setTimeout(() => {
                        if (document.body.contains(dynamicInput)) {
                            document.body.removeChild(dynamicInput);
                            log('🧹 Dynamic input cleaned up');
                        }
                    }, 100);
                });
                
                document.body.appendChild(dynamicInput);
                log('🔧 Dynamic input created and added to DOM');
                
                // Try to trigger
                setTimeout(() => {
                    dynamicInput.click();
                    log('📁 Dynamic input dialog triggered');
                }, 10);
                
                // Set timeout to detect freeze
                setTimeout(() => {
                    if (document.body.contains(dynamicInput) && (!dynamicInput.files || dynamicInput.files.length === 0)) {
                        log('🚨 WARNING: Dynamic input may have frozen - this is common on Windows');
                        if (document.body.contains(dynamicInput)) {
                            document.body.removeChild(dynamicInput);
                            log('🧹 Cleaned up potentially frozen dynamic input');
                        }
                    }
                }, 10000);
                
            } catch (error) {
                log(`❌ ERROR: Dynamic input creation failed - ${error.message}`);
            }
        }

        // Test 4: Drag & Drop setup
        function setupDragDrop() {
            const dropZone = document.getElementById('dropZone');
            
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                dropZone.style.background = '#e8f5e8';
                log('📎 Files dragged over drop zone');
            });
            
            dropZone.addEventListener('dragleave', function(e) {
                dropZone.style.background = '#f8fff8';
            });
            
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                dropZone.style.background = '#f8fff8';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    log(`✅ SUCCESS: Drag & drop received ${files.length} file(s)`);
                    for (let i = 0; i < files.length; i++) {
                        log(`   📄 File ${i+1}: ${files[i].name} (${files[i].size} bytes)`);
                    }
                } else {
                    log('📁 Drag & drop: No files received');
                }
            });
        }

        // Diagnostic function
        function runDiagnostics() {
            log('🔍 RUNNING SYSTEM DIAGNOSTICS...');
            log(`🌐 User Agent: ${navigator.userAgent}`);
            log(`🌐 Platform: ${navigator.platform}`);
            log(`🌐 Browser: ${navigator.appName} ${navigator.appVersion}`);
            log(`💾 LocalStorage available: ${typeof(Storage) !== "undefined"}`);
            log(`🎯 File API supported: ${!!(window.File && window.FileReader && window.FileList && window.Blob)}`);
            log(`📎 Drag & Drop supported: ${('draggable' in document.createElement('span')) && ('ondrop' in window)}`);
            
            // Test file input creation
            try {
                const testInput = document.createElement('input');
                testInput.type = 'file';
                log('✅ Can create file input elements');
            } catch (error) {
                log(`❌ Cannot create file input elements: ${error.message}`);
            }
            
            log('🔍 DIAGNOSTICS COMPLETE');
        }

        function clearLog() {
            document.getElementById('log').textContent = '';
            log('🧹 Log cleared');
        }

        function forceRefresh() {
            log('🔄 Forcing hard refresh...');
            if (typeof(Storage) !== "undefined") {
                localStorage.clear();
                sessionStorage.clear();
            }
            setTimeout(() => {
                window.location.reload(true);
            }, 500);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 File Upload Test Page Loaded');
            setupDragDrop();
            runDiagnostics();
        });
    </script>
</body>
</html>