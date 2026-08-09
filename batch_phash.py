import sys
import json
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
    except Exception:
        return None

if __name__ == '__main__':
    # Read list of files from stdin JSON
    try:
        input_data = sys.stdin.read()
        paths = json.loads(input_data)
        
        results = {}
        for path in paths:
            try:
                img = Image.open(path)
                h = dhash(img)
                if h:
                    results[path] = h
            except Exception:
                pass
                
        print(json.dumps(results))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)
