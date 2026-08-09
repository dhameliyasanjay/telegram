import sys
from PIL import Image

def dhash(image, hash_size=8):
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

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python phash.py <image_path>")
        sys.exit(1)
        
    try:
        img = Image.open(sys.argv[1])
        print(dhash(img))
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)
