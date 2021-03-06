<?php namespace Jenssegers\ImageHash;

use Exception;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

class ImageHash
{
    /**
     * Return hashes as hexacedimals.
     */
    const HEXADECIMAL = 'hex';

    /**
     * Return hashes as decimals.
     */
    const DECIMAL = 'dec';

    /**
     * The hashing implementation.
     *
     * @var Implementation
     */
    protected $implementation;

    /**
     * Constructor.
     *
     * @param Implementation $implementation
     * @param string $mode
     */
    public function __construct(Implementation $implementation = null, $mode = self::HEXADECIMAL)
    {
        $this->implementation = $implementation ?: new DifferenceHash;

        $this->mode = $mode;
    }

    /**
     * Calculate a perceptual hash of an image file.
     *
     * @param  mixed   $resource GD2 resource or filename
     * @return int
     */
    public function hash($resource)
    {
        $destroy = false;

        if (! is_resource($resource)) {
            $resource = $this->loadImageResource($resource);
            $destroy = true;
        }

        $hash = $this->implementation->hash($resource);

        if ($destroy) {
            $this->destroyResource($resource);
        }

        return $this->formatHash($hash);
    }

    /**
     * Calculate a perceptual hash of an image string.
     *
     * @param  mixed $data Image data
     * @return string
     */
    public function hashString($data)
    {
        $resource = $this->createResource($data);

        $hash = $this->implementation->hash($resource);

        $this->destroyResource($resource);

        return $this->formatHash($hash);
    }

    /**
     * Compare 2 images and get the hamming distance.
     *
     * @param  mixed $resource1
     * @param  mixed $resource2
     * @return int
     */
    public function compare($resource1, $resource2)
    {
        $hash1 = $this->hash($resource1);
        $hash2 = $this->hash($resource2);

        return $this->distance($hash1, $hash2);
    }

    /**
     * Calculate the Hamming Distance.
     *
     * @param int $hash1
     * @param int $hash2
     * @return int
     */
    public function distance($hash1, $hash2)
    {
        if (extension_loaded('gmp')) {
            if ($this->mode === self::HEXADECIMAL) {
                $dh = gmp_hamdist('0x' . $hash1, '0x' . $hash2);
            } else {
                $dh = gmp_hamdist($hash1, $hash2);
            }
        } else {
            if ($this->mode === self::HEXADECIMAL) {
                $hash1 = hexdec($hash1);
                $hash2 = hexdec($hash2);
            }

            $dh = 0;
            for ($i = 0; $i < 64; $i++) {
                $k = (1 << $i);
                if (($hash1 & $k) !== ($hash2 & $k)) {
                    $dh++;
                }
            }
        }

        return $dh;
    }

    /**
     * Get a GD2 resource from file.
     *
     * @param  string   $file
     * @return resource
     */
    protected function loadImageResource($file)
    {
        try {
            return $this->createResource(file_get_contents($file));
        } catch (Exception $e) {
            throw new Exception("Unable to load file: $file");
        }
    }

    /**
     * Get a GD2 resource from string.
     *
     * @param string $data
     * @return resource
     */
    protected function createResource($data)
    {
        try {
            return imagecreatefromstring($data);
        } catch (Exception $e) {
            throw new Exception("Unable to create GD2 resource");
        }
    }

    /**
     * Destroy GD2 resource.
     *
     * @param resource $resource
     */
    protected function destroyResource($resource)
    {
        imagedestroy($resource);
    }

    /**
     * Format hash in hex.
     *
     * @param int $hash
     * @return string|int
     */
    protected function formatHash($hash)
    {
        return $this->mode === static::HEXADECIMAL ? dechex($hash) : $hash;
    }
}
