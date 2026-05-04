const fs = require('fs');

function convertFile(filename, newFilename) {
    let content = fs.readFileSync(filename, 'utf8');

    // 1. Remove supabase import
    content = content.replace(/<script src="https:\/\/cdn\.jsdelivr\.net\/npm\/@supabase\/supabase-js@2"><\/script>/g, '');

    // 2. Remove supabase init
    content = content.replace(/const supabaseUrl.*?;/g, '');
    content = content.replace(/const supabaseKey.*?;/g, '');
    content = content.replace(/const supabaseClient.*?;/g, 'const supabaseClient = window.supabase.createClient();');

    // 3. Include polyfill instead
    if (!content.includes('supabase-polyfill.js')) {
        content = content.replace(/<script>/, '<script src="supabase-polyfill.js"></script>\n    <script>');
    }

    fs.writeFileSync(newFilename, content, 'utf8');
    console.log(`Converted ${filename} to ${newFilename}`);
}

convertFile('index.html', 'index.php');
convertFile('seller.html', 'seller.php');
