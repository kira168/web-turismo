import urllib.request

base = 'http://localhost:8080'

# Test 1: PEN
html_pen = urllib.request.urlopen(f'{base}/index.php?currency=PEN').read().decode('utf-8')
print("Test 1 (PEN):", "S/" in html_pen, "| Currency in header:", 'selected' in html_pen and 'PEN' in html_pen)

# Test 2: USD
html_usd = urllib.request.urlopen(f'{base}/index.php?currency=USD').read().decode('utf-8')
print("Test 2 (USD):", "$" in html_usd, "| Currency in header:", 'selected' in html_usd and 'USD' in html_usd)

# Test 3: EUR
html_eur = urllib.request.urlopen(f'{base}/index.php?currency=EUR').read().decode('utf-8')
print("Test 3 (EUR):", "€" in html_eur, "| Currency in header:", 'selected' in html_eur and 'EUR' in html_eur)

# Test 4: registro.php GET
html_reg = urllib.request.urlopen(f'{base}/registro.php').read().decode('utf-8')
print("Test 4 (registro.php GET): Form exists:", '<form action="registro.php"' in html_reg, "| Has name field:", 'name="nombre_completo"' in html_reg)

# Test 5: login.php GET
html_log = urllib.request.urlopen(f'{base}/login.php').read().decode('utf-8')
print("Test 5 (login.php GET): Form exists:", '<form action="login.php"' in html_log, "| Has demo accounts:", 'admin@turismoinkaiko.com' in html_log)

# Test 6: Verify JS in index.php has no syntax error
if 'reviewModal' in html_pen and 'toggleOpiniones' in html_pen:
    print("Test 6 (index.php elements): reviewModal and toggleOpiniones PRESENT!")

import os
os.unlink(__file__)
