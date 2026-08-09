import os
import sys
import mysql.connector
from PIL import Image

def dhash(image, hash_size=8):
    try:
        image = image.convert('L').resize((hash_size + 1, hash_size), Image.Resampling.LANCZOS)
        pixels = list(image.getdata())
        
        diff = []
        for row in range(hash_size):
            for col in range(hash_size):
                pixel_left = image.getpixel((col, row))
                pixel_right = image.getpixel((col + 1, row))
                diff.append(pixel_left > pixel_right)
                
        decimal_value = 0
        hex_string = []
        for index, value in enumerate(diff):
            if value:
                decimal_value += 2**(index % 8)
            if (index % 8) == 7:
                hex_string.append(hex(decimal_value)[2:].rjust(2, '0'))
                decimal_value = 0
                
        return ''.join(hex_string)
    except Exception as e:
        return None

try:
    conn = mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="Admin@123",
        database="telegram"
    )
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT id, latest_photo_url FROM products WHERE latest_photo_url IS NOT NULL")
    products = cursor.fetchall()
    
    updates = []
    
    print(f"Hashing {len(products)} photos...")
    for p in products:
        path = p['latest_photo_url'].replace('/storage/', 'storage/')
        full_path = os.path.join(os.getcwd(), 'public', path)
        
        if os.path.exists(full_path):
            try:
                img = Image.open(full_path)
                h = dhash(img)
                if h:
                    updates.append((h, p['id']))
            except Exception:
                pass

    print(f"Updating {len(updates)} records in database...")
    
    update_cursor = conn.cursor()
    update_cursor.executemany("UPDATE products SET photo_hash = %s WHERE id = %s", updates)
    conn.commit()
    
    print("Hashing backfill completed successfully!")
    
except Exception as e:
    print(f"Error: {e}")
finally:
    if 'conn' in locals() and conn.is_connected():
        conn.close()
