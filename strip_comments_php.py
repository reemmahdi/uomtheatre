# تنظيف التعليقات من كود لارافل (app/ و routes/ و database/migrations)
# وتعليقات بليد {{-- --}} من resources/views
# التشغيل من جذر مشروع الويب:  python3 strip_comments_php.py
# نسخة احتياطية php_backup.zip قبل أي تعديل
# ملاحظة: تعليقات # لا تُمس إطلاقاً (لتفادي أي التباس مع #[Attributes])

import os
import re
import zipfile

PHP_DIRS = ['app', 'routes']
MIG_DIR = os.path.join('database', 'migrations')
BLADE_DIR = os.path.join('resources', 'views')


def strip_php(src):
    out = []
    i = 0
    n = len(src)
    in_line = False
    in_block = False
    in_str = None
    while i < n:
        c = src[i]
        nxt = src[i + 1] if i + 1 < n else ''
        if in_line:
            if c == '\n':
                in_line = False
                out.append(c)
            i += 1
            continue
        if in_block:
            if c == '*' and nxt == '/':
                in_block = False
                i += 2
            else:
                if c == '\n':
                    out.append(c)
                i += 1
            continue
        if in_str is not None:
            out.append(c)
            if c == '\\' and i + 1 < n:
                out.append(src[i + 1])
                i += 2
                continue
            if c == in_str:
                in_str = None
            i += 1
            continue
        if c in ('"', "'"):
            in_str = c
            out.append(c)
            i += 1
            continue
        if c == '/' and nxt == '/':
            in_line = True
            i += 2
            continue
        if c == '/' and nxt == '*':
            in_block = True
            i += 2
            continue
        out.append(c)
        i += 1

    lines = [l.rstrip() for l in ''.join(out).split('\n')]
    cleaned = []
    blank = 0
    for l in lines:
        if l.strip() == '':
            blank += 1
            if blank <= 1:
                cleaned.append('')
        else:
            blank = 0
            cleaned.append(l)
    return '\n'.join(cleaned).rstrip() + '\n'


def strip_blade(src):
    return re.sub(r'\{\{--.*?--\}\}\n?', '', src, flags=re.DOTALL)


def collect():
    php, blades = [], []
    for d in PHP_DIRS + [MIG_DIR]:
        if not os.path.isdir(d):
            continue
        for root, _dirs, names in os.walk(d):
            for name in names:
                if name.endswith('.php'):
                    php.append(os.path.join(root, name))
    if os.path.isdir(BLADE_DIR):
        for root, _dirs, names in os.walk(BLADE_DIR):
            for name in names:
                if name.endswith('.blade.php'):
                    blades.append(os.path.join(root, name))
    return php, blades


def main():
    if not os.path.isdir('app'):
        print('شغلي السكربت من جذر مشروع الويب (المجلد الذي فيه app)')
        return

    php, blades = collect()

    with zipfile.ZipFile('php_backup.zip', 'w', zipfile.ZIP_DEFLATED) as z:
        for f in php + blades:
            z.write(f)
    print('نسخة احتياطية: php_backup.zip ('
          + str(len(php) + len(blades)) + ' ملفاً)')

    changed = 0
    for f in php:
        with open(f, encoding='utf-8') as fh:
            src = fh.read()
        result = strip_php(src)
        if result != src:
            with open(f, 'w', encoding='utf-8') as fh:
                fh.write(result)
            changed += 1
    for f in blades:
        with open(f, encoding='utf-8') as fh:
            src = fh.read()
        result = strip_blade(src)
        if result != src:
            with open(f, 'w', encoding='utf-8') as fh:
                fh.write(result)
            changed += 1
    print('نُظفت التعليقات من ' + str(changed) + ' ملفاً')
    print('الآن: php artisan optimize:clear ثم جربي اللوحة والتطبيق')


main()
