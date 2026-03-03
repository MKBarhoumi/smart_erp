const fs = require('fs');
const path = require('path');

// Read the markdown file
const markdownPath = path.join(__dirname, 'SOAP_API_COMPLETE_REFERENCE.md');
const cssPath = path.join(__dirname, 'pdf-styles.css');
const outputPath = path.join(__dirname, 'SOAP_API_COMPLETE_REFERENCE.pdf');

const markdown = fs.readFileSync(markdownPath, 'utf8');
const css = fs.readFileSync(cssPath, 'utf8');

// Simple markdown to HTML converter
function markdownToHtml(md) {
    let html = md;
    
    // Escape HTML entities in content (but not our generated HTML)
    // We'll handle this per-section

    // Code blocks with language (```xml, ```json, etc.)
    html = html.replace(/```(\w+)\n([\s\S]*?)```/g, (match, lang, code) => {
        const escapedCode = code
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        return `<pre><code class="language-${lang}">${escapedCode}</code></pre>`;
    });

    // Code blocks without language
    html = html.replace(/```\n([\s\S]*?)```/g, (match, code) => {
        const escapedCode = code
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        return `<pre><code>${escapedCode}</code></pre>`;
    });

    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

    // Headers
    html = html.replace(/^######\s+(.+)$/gm, '<h6>$1</h6>');
    html = html.replace(/^#####\s+(.+)$/gm, '<h5>$1</h5>');
    html = html.replace(/^####\s+(.+)$/gm, '<h4>$1</h4>');
    html = html.replace(/^###\s+(.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^##\s+(.+)$/gm, '<h2>$1</h2>');
    html = html.replace(/^#\s+(.+)$/gm, '<h1>$1</h1>');

    // Bold
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

    // Italic
    html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');

    // Horizontal rules
    html = html.replace(/^---+$/gm, '<hr>');

    // Blockquotes
    html = html.replace(/^>\s*(.+)$/gm, '<blockquote><p>$1</p></blockquote>');
    // Merge adjacent blockquotes
    html = html.replace(/<\/blockquote>\s*<blockquote>/g, '\n');

    // Tables
    html = html.replace(/\|(.+)\|\n\|[-:\s|]+\|\n((?:\|.+\|\n?)*)/g, (match, header, body) => {
        const headerCells = header.split('|').map(s => s.trim()).filter(s => s);
        const headerRow = '<tr>' + headerCells.map(cell => `<th>${cell}</th>`).join('') + '</tr>';
        
        const bodyRows = body.trim().split('\n').map(row => {
            const cells = row.split('|').map(s => s.trim()).filter(s => s);
            return '<tr>' + cells.map(cell => `<td>${cell}</td>`).join('') + '</tr>';
        }).join('\n');
        
        return `<table><thead>${headerRow}</thead><tbody>${bodyRows}</tbody></table>`;
    });

    // Unordered lists
    html = html.replace(/^(\s*)[-*]\s+(.+)$/gm, (match, indent, content) => {
        const level = Math.floor(indent.length / 2);
        return `<li data-level="${level}">${content}</li>`;
    });
    
    // Wrap consecutive li elements in ul
    html = html.replace(/((?:<li[^>]*>[^<]*<\/li>\n?)+)/g, '<ul>$1</ul>');

    // Ordered lists
    html = html.replace(/^(\s*)\d+\.\s+(.+)$/gm, '<li>$2</li>');

    // Links
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');

    // Paragraphs - wrap text that isn't already in a tag
    const lines = html.split('\n');
    const processedLines = [];
    let inPre = false;
    
    for (let line of lines) {
        if (line.includes('<pre>')) inPre = true;
        if (line.includes('</pre>')) inPre = false;
        
        if (!inPre && line.trim() && 
            !line.trim().startsWith('<') && 
            !line.trim().endsWith('>')) {
            processedLines.push(`<p>${line}</p>`);
        } else {
            processedLines.push(line);
        }
    }
    html = processedLines.join('\n');

    // Clean up empty paragraphs
    html = html.replace(/<p>\s*<\/p>/g, '');

    return html;
}

const htmlContent = markdownToHtml(markdown);

const fullHtml = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elfatoora SOAP API - Complete Technical Reference</title>
    <style>
${css}

/* Additional PDF-specific styles */
@page {
    size: A4;
    margin: 20mm 15mm 25mm 15mm;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 10pt;
    line-height: 1.6;
    color: #2d3748;
    max-width: 100%;
    margin: 0;
    padding: 0;
}

/* Cover styling */
.cover {
    text-align: center;
    padding: 60px 20px;
    border-bottom: 3px solid #2c5282;
    margin-bottom: 40px;
    page-break-after: always;
}

.cover h1 {
    font-size: 28pt;
    color: #1a365d;
    margin-bottom: 20px;
    border: none;
}

.cover .subtitle {
    font-size: 14pt;
    color: #4a5568;
    margin-bottom: 30px;
}

.cover .version-info {
    font-size: 11pt;
    color: #718096;
    line-height: 2;
}

.cover .version-info strong {
    color: #2d3748;
}

/* Header improvements */
h1 {
    font-size: 22pt;
    color: #1a365d;
    border-bottom: 3px solid #2c5282;
    padding-bottom: 8px;
    margin-top: 0;
    margin-bottom: 20px;
    page-break-after: avoid;
}

h2 {
    font-size: 16pt;
    color: #2c5282;
    border-bottom: 2px solid #4299e1;
    padding-bottom: 5px;
    margin-top: 35px;
    margin-bottom: 15px;
    page-break-after: avoid;
    page-break-before: always;
}

h2:first-of-type {
    page-break-before: avoid;
}

h3 {
    font-size: 13pt;
    color: #2b6cb0;
    margin-top: 25px;
    margin-bottom: 10px;
    page-break-after: avoid;
}

h4 {
    font-size: 11pt;
    color: #3182ce;
    margin-top: 18px;
    margin-bottom: 8px;
    page-break-after: avoid;
}

/* Table improvements */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
    font-size: 9pt;
    page-break-inside: avoid;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

thead {
    display: table-header-group;
}

th {
    background: linear-gradient(135deg, #2c5282 0%, #2b6cb0 100%);
    color: white;
    font-weight: 600;
    text-align: left;
    padding: 10px 12px;
    border: 1px solid #2c5282;
    font-size: 9pt;
}

td {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    vertical-align: top;
}

tr:nth-child(even) {
    background-color: #f7fafc;
}

tr:nth-child(odd) {
    background-color: #ffffff;
}

tr:hover {
    background-color: #edf2f7;
}

/* Code block improvements */
pre {
    background-color: #1a202c;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 8.5pt;
    line-height: 1.4;
    margin: 15px 0;
    page-break-inside: avoid;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

pre code {
    background: none;
    padding: 0;
    font-size: inherit;
    color: inherit;
}

code {
    background-color: #edf2f7;
    color: #2d3748;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 9pt;
}

/* Blockquote improvements */
blockquote {
    border-left: 4px solid #4299e1;
    background-color: #ebf8ff;
    padding: 12px 20px;
    margin: 15px 0;
    border-radius: 0 6px 6px 0;
    page-break-inside: avoid;
}

blockquote p {
    margin: 0;
    color: #2c5282;
}

/* Warning blockquotes */
blockquote p:first-child strong:first-child {
    color: #c05621;
}

/* List improvements */
ul, ol {
    margin: 10px 0;
    padding-left: 25px;
}

li {
    margin: 5px 0;
}

/* HR styling */
hr {
    border: none;
    height: 2px;
    background: linear-gradient(90deg, #e2e8f0 0%, #4299e1 50%, #e2e8f0 100%);
    margin: 30px 0;
}

/* Link styling */
a {
    color: #2b6cb0;
    text-decoration: none;
}

/* Status indicators */
td:contains("✅"), 
td:contains("❌"),
td:contains("⏳") {
    text-align: center;
}

/* Footer */
.footer {
    text-align: center;
    font-size: 9pt;
    color: #718096;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}
    </style>
</head>
<body>
    ${htmlContent}
    <div class="footer">
        <p><em>Document generated for Smart ERP - Elfatoora Integration</em></p>
        <p>TEIF v1.8.8 Compliance - Tunisia TradeNet (TTN)</p>
    </div>
</body>
</html>`;

// Write HTML file first
const htmlOutputPath = path.join(__dirname, 'SOAP_API_COMPLETE_REFERENCE.html');
fs.writeFileSync(htmlOutputPath, fullHtml, 'utf8');

console.log('HTML file created successfully at:', htmlOutputPath);
console.log('Please use a browser or PDF printer to convert to PDF.');
console.log('Now attempting PDF generation with puppeteer...');

// Try to use puppeteer-core with existing browser
async function generatePdf() {
    try {
        const puppeteer = require('puppeteer-core');
        
        // Try different browser paths on Windows
        const browserPaths = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            process.env.LOCALAPPDATA + '\\Google\\Chrome\\Application\\chrome.exe',
            process.env.LOCALAPPDATA + '\\Microsoft\\Edge\\Application\\msedge.exe'
        ];
        
        let executablePath = null;
        for (const browserPath of browserPaths) {
            if (fs.existsSync(browserPath)) {
                executablePath = browserPath;
                console.log('Found browser at:', browserPath);
                break;
            }
        }
        
        if (!executablePath) {
            throw new Error('No browser found. Please install Chrome or Edge.');
        }
        
        const browser = await puppeteer.launch({ 
            headless: true,
            executablePath: executablePath,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
        });
        const page = await browser.newPage();
        
        await page.setContent(fullHtml, { waitUntil: 'networkidle0' });
        
        await page.pdf({
            path: outputPath,
            format: 'A4',
            margin: {
                top: '25mm',
                right: '15mm',
                bottom: '25mm',
                left: '15mm'
            },
            printBackground: true,
            displayHeaderFooter: true,
            headerTemplate: '<div style="font-size: 9px; color: #888; width: 100%; text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd;">Elfatoora SOAP API - Complete Technical Reference</div>',
            footerTemplate: '<div style="font-size: 9px; color: #888; width: 100%; text-align: center; padding: 10px 0;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>'
        });
        
        await browser.close();
        console.log('PDF generated successfully at:', outputPath);
    } catch (error) {
        console.log('Error occurred:', error.message);
        console.log('Please open the HTML file in a browser and use Print > Save as PDF.');
    }
}

generatePdf();
