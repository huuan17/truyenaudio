import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';

import fs from 'fs/promises';
import path from 'path';

puppeteer.use(StealthPlugin());

// Nhận tham số từ command line
const [, , baseUrl, start, end, outputFolder, singleMode] = process.argv;

if (!baseUrl || !start || !end || !outputFolder) {
  console.error('❌ Thiếu tham số. Cú pháp: node crawl.js <baseUrl> <startChapter> <endChapter> <outputFolder> [singleMode]');
  process.exit(1);
}

const START_CHAPTER = parseInt(start);
const END_CHAPTER = parseInt(end);
const SINGLE_MODE = singleMode === '1'; // Chế độ crawl một chương duy nhất

// Tạo thư mục lưu nội dung nếu chưa có
try {
  await fs.mkdir(outputFolder, { recursive: true });
  console.log(`✅ Đã tạo/thấy thư mục: ${outputFolder}`);
} catch (e) {
  console.error('❌ Lỗi tạo thư mục:', e.message);
  process.exit(1);
}

// Sửa đổi ở đây: Thêm executablePath để chỉ định đường dẫn đến Chrome
const browser = await puppeteer.launch({ 
  headless: 'new',
  executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || 
    (process.platform === 'win32' 
      ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
      : (process.platform === 'linux'
        ? '/usr/bin/google-chrome'
        : '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome')),
  args: ['--no-sandbox', '--disable-setuid-sandbox']
});

const page = await browser.newPage();

// Nếu ở chế độ single mode, chỉ log ra thông tin cần thiết
if (!SINGLE_MODE) {
  console.log(`🔍 Bắt đầu crawl từ chương ${START_CHAPTER} đến chương ${END_CHAPTER}`);
}

for (let chapter = START_CHAPTER; chapter <= END_CHAPTER; chapter++) {
  const url = `${baseUrl}${chapter}.html`;
  if (!SINGLE_MODE) {
    console.log(`📖 Đang truy cập: ${url}`);
  }

  try {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });

    const content = await page.$eval('div.chapter-c', el => el.innerText);

    const filename = path.join(outputFolder, `chuong-${chapter}.txt`);
    await fs.writeFile(filename, content, 'utf-8');

    if (!SINGLE_MODE) {
      console.log(`✅ Đã lưu chương ${chapter} → ${filename}`);
    }
    
  } catch (e) {
    console.error(`❌ Lỗi ở chương ${chapter}:`, e.message);
    if (SINGLE_MODE) {
      await browser.close();
      process.exit(1);
    }
  }
}

await browser.close();
if (!SINGLE_MODE) {
  console.log('🎉 Đã crawl xong tất cả chương.');
}
