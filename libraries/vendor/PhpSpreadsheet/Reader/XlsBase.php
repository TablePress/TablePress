<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Reader;

use TablePress\PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\CodePage;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\File;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\OLERead;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use TablePress\PhpOffice\PhpSpreadsheet\Style\Border;

class XlsBase extends BaseReader
{
	protected const HIGH_ORDER_BIT = 0x80 << 24;
	protected const FC000000 = 0xFC << 24;
	protected const FE000000 = 0xFE << 24;

	// ParseXL definitions
	const XLS_BIFF8 = 0x0600;
	const XLS_BIFF7 = 0x0500;
	const XLS_WORKBOOKGLOBALS = 0x0005;
	const XLS_WORKSHEET = 0x0010;

	// record identifiers
	const XLS_TYPE_FORMULA = 0x0006;
	const XLS_TYPE_EOF = 0x000A;
	const XLS_TYPE_PROTECT = 0x0012;
	const XLS_TYPE_OBJECTPROTECT = 0x0063;
	const XLS_TYPE_SCENPROTECT = 0x00DD;
	const XLS_TYPE_PASSWORD = 0x0013;
	const XLS_TYPE_HEADER = 0x0014;
	const XLS_TYPE_FOOTER = 0x0015;
	const XLS_TYPE_EXTERNSHEET = 0x0017;
	const XLS_TYPE_DEFINEDNAME = 0x0018;
	const XLS_TYPE_VERTICALPAGEBREAKS = 0x001A;
	const XLS_TYPE_HORIZONTALPAGEBREAKS = 0x001B;
	const XLS_TYPE_NOTE = 0x001C;
	const XLS_TYPE_SELECTION = 0x001D;
	const XLS_TYPE_DATEMODE = 0x0022;
	const XLS_TYPE_EXTERNNAME = 0x0023;
	const XLS_TYPE_LEFTMARGIN = 0x0026;
	const XLS_TYPE_RIGHTMARGIN = 0x0027;
	const XLS_TYPE_TOPMARGIN = 0x0028;
	const XLS_TYPE_BOTTOMMARGIN = 0x0029;
	const XLS_TYPE_PRINTGRIDLINES = 0x002B;
	const XLS_TYPE_FILEPASS = 0x002F;
	const XLS_TYPE_FONT = 0x0031;
	const XLS_TYPE_CONTINUE = 0x003C;
	const XLS_TYPE_PANE = 0x0041;
	const XLS_TYPE_CODEPAGE = 0x0042;
	const XLS_TYPE_DEFCOLWIDTH = 0x0055;
	const XLS_TYPE_OBJ = 0x005D;
	const XLS_TYPE_COLINFO = 0x007D;
	const XLS_TYPE_IMDATA = 0x007F;
	const XLS_TYPE_SHEETPR = 0x0081;
	const XLS_TYPE_HCENTER = 0x0083;
	const XLS_TYPE_VCENTER = 0x0084;
	const XLS_TYPE_SHEET = 0x0085;
	const XLS_TYPE_PALETTE = 0x0092;
	const XLS_TYPE_SCL = 0x00A0;
	const XLS_TYPE_PAGESETUP = 0x00A1;
	const XLS_TYPE_MULRK = 0x00BD;
	const XLS_TYPE_MULBLANK = 0x00BE;
	const XLS_TYPE_DBCELL = 0x00D7;
	const XLS_TYPE_XF = 0x00E0;
	const XLS_TYPE_MERGEDCELLS = 0x00E5;
	const XLS_TYPE_MSODRAWINGGROUP = 0x00EB;
	const XLS_TYPE_MSODRAWING = 0x00EC;
	const XLS_TYPE_SST = 0x00FC;
	const XLS_TYPE_LABELSST = 0x00FD;
	const XLS_TYPE_EXTSST = 0x00FF;
	const XLS_TYPE_EXTERNALBOOK = 0x01AE;
	const XLS_TYPE_DATAVALIDATIONS = 0x01B2;
	const XLS_TYPE_TXO = 0x01B6;
	const XLS_TYPE_HYPERLINK = 0x01B8;
	const XLS_TYPE_DATAVALIDATION = 0x01BE;
	const XLS_TYPE_DIMENSION = 0x0200;
	const XLS_TYPE_BLANK = 0x0201;
	const XLS_TYPE_NUMBER = 0x0203;
	const XLS_TYPE_LABEL = 0x0204;
	const XLS_TYPE_BOOLERR = 0x0205;
	const XLS_TYPE_STRING = 0x0207;
	const XLS_TYPE_ROW = 0x0208;
	const XLS_TYPE_INDEX = 0x020B;
	const XLS_TYPE_ARRAY = 0x0221;
	const XLS_TYPE_DEFAULTROWHEIGHT = 0x0225;
	const XLS_TYPE_WINDOW2 = 0x023E;
	const XLS_TYPE_RK = 0x027E;
	const XLS_TYPE_STYLE = 0x0293;
	const XLS_TYPE_FORMAT = 0x041E;
	const XLS_TYPE_SHAREDFMLA = 0x04BC;
	const XLS_TYPE_BOF = 0x0809;
	const XLS_TYPE_SHEETPROTECTION = 0x0867;
	const XLS_TYPE_RANGEPROTECTION = 0x0868;
	const XLS_TYPE_SHEETLAYOUT = 0x0862;
	const XLS_TYPE_XFEXT = 0x087D;
	const XLS_TYPE_PAGELAYOUTVIEW = 0x088B;
	const XLS_TYPE_CFHEADER = 0x01B0;
	const XLS_TYPE_CFRULE = 0x01B1;
	const XLS_TYPE_UNKNOWN = 0xFFFF;

	// Encryption type
	const MS_BIFF_CRYPTO_NONE = 0;
	const MS_BIFF_CRYPTO_XOR = 1;
	const MS_BIFF_CRYPTO_RC4 = 2;

	// Size of stream blocks when using RC4 encryption
	const REKEY_BLOCK = 0x400;

	// should be consistent with Writer\Xls\Style\CellBorder
	const BORDER_STYLE_MAP = [
		Border::BORDER_NONE, // => 0x00,
		Border::BORDER_THIN,  // => 0x01,
		Border::BORDER_MEDIUM, // => 0x02,
		Border::BORDER_DASHED, // => 0x03,
		Border::BORDER_DOTTED,  // => 0x04,
		Border::BORDER_THICK, // => 0x05,
		Border::BORDER_DOUBLE, // => 0x06,
		Border::BORDER_HAIR, // => 0x07,
		Border::BORDER_MEDIUMDASHED, // => 0x08,
		Border::BORDER_DASHDOT, // => 0x09,
		Border::BORDER_MEDIUMDASHDOT, // => 0x0A,
		Border::BORDER_DASHDOTDOT, // => 0x0B,
		Border::BORDER_MEDIUMDASHDOTDOT, // => 0x0C,
		Border::BORDER_SLANTDASHDOT, // => 0x0D,
		Border::BORDER_OMIT, // => 0x0E,
		Border::BORDER_OMIT, // => 0x0F,
	];

	/**
	 * Codepage set in the Excel file being read. Only important for BIFF5 (Excel 5.0 - Excel 95)
	 * For BIFF8 (Excel 97 - Excel 2003) this will always have the value 'UTF-16LE'.
	 */
	protected string $codepage = '';

	public function setCodepage(string $codepage): void
	{
		if (CodePage::validate($codepage) === false) {
			throw new PhpSpreadsheetException('Unknown codepage: ' . $codepage);
		}

		$this->codepage = $codepage;
	}

	public function getCodepage(): string
	{
		return $this->codepage;
	}

	/**
	 * Can the current IReader read the file?
	 */
	public function canRead(string $filename): bool
	{
		if (File::testFileNoThrow($filename) === false) {
			return false;
		}

		try {
			// Use ParseXL for the hard work.
			$ole = new OLERead();

			// get excel data
			$ole->read($filename);
			if ($ole->wrkbook === null) {
				throw new Exception('The filename ' . $filename . ' is not recognised as a Spreadsheet file');
			}

			return true;
		} catch (PhpSpreadsheetException $exception) {
			return false;
		}
	}

	/**
	 * Extract RGB color
	 * OpenOffice.org's Documentation of the Microsoft Excel File Format, section 2.5.4.
	 *
	 * @param string $rgb Encoded RGB value (4 bytes)
	 */
	protected static function readRGB(string $rgb): array
	{
		// offset: 0; size 1; Red component
		$r = ord($rgb[0]);

		// offset: 1; size: 1; Green component
		$g = ord($rgb[1]);

		// offset: 2; size: 1; Blue component
		$b = ord($rgb[2]);

		// HEX notation, e.g. 'FF00FC'
		$rgb = sprintf('%02X%02X%02X', $r, $g, $b);

		return ['rgb' => $rgb];
	}

	/**
	 * Extracts an Excel Unicode short string (8-bit string length)
	 * OpenOffice documentation: 2.5.3
	 * function will automatically find out where the Unicode string ends.
	 */
	protected static function readUnicodeStringShort(string $subData): array
	{
		// offset: 0: size: 1; length of the string (character count)
		$characterCount = ord($subData[0]);

		$string = self::readUnicodeString(substr($subData, 1), $characterCount);

		// add 1 for the string length
		++$string['size'];

		return $string;
	}

	/**
	 * Extracts an Excel Unicode long string (16-bit string length)
	 * OpenOffice documentation: 2.5.3
	 * this function is under construction, needs to support rich text, and Asian phonetic settings.
	 */
	protected static function readUnicodeStringLong(string $subData): array
	{
		// offset: 0: size: 2; length of the string (character count)
		$characterCount = self::getUInt2d($subData, 0);

		$string = self::readUnicodeString(substr($subData, 2), $characterCount);

		// add 2 for the string length
		$string['size'] += 2;

		return $string;
	}

	/**
	 * Read Unicode string with no string length field, but with known character count
	 * this function is under construction, needs to support rich text, and Asian phonetic settings
	 * OpenOffice.org's Documentation of the Microsoft Excel File Format, section 2.5.3.
	 */
	protected static function readUnicodeString(string $subData, int $characterCount): array
	{
		// offset: 0: size: 1; option flags
		// bit: 0; mask: 0x01; character compression (0 = compressed 8-bit, 1 = uncompressed 16-bit)
		$isCompressed = !((0x01 & ord($subData[0])) >> 0);

		// bit: 2; mask: 0x04; Asian phonetic settings
		//$hasAsian = (0x04) & ord($subData[0]) >> 2;

		// bit: 3; mask: 0x08; Rich-Text settings
		//$hasRichText = (0x08) & ord($subData[0]) >> 3;

		// offset: 1: size: var; character array
		// this offset assumes richtext and Asian phonetic settings are off which is generally wrong
		// needs to be fixed
		$value = self::encodeUTF16(substr($subData, 1, $isCompressed ? $characterCount : 2 * $characterCount), $isCompressed);

		return [
			'value' => $value,
			'size' => $isCompressed ? 1 + $characterCount : 1 + 2 * $characterCount, // the size in bytes including the option flags
		];
	}

	/**
	 * Convert UTF-8 string to string surounded by double quotes. Used for explicit string tokens in formulas.
	 * Example:  hello"world  -->  "hello""world".
	 *
	 * @param string $value UTF-8 encoded string
	 */
	protected static function UTF8toExcelDoubleQuoted(string $value): string
	{
		return '"' . str_replace('"', '""', $value) . '"';
	}

	/**
				 * Reads first 8 bytes of a string and return IEEE 754 float.
				 *
				 * @param string $data Binary string that is at least 8 bytes long
				 * @return float|int
				 */
				protected static function extractNumber(string $data)
	{
		$rknumhigh = self::getInt4d($data, 4);
		$rknumlow = self::getInt4d($data, 0);
		$sign = ($rknumhigh & self::HIGH_ORDER_BIT) >> 31;
		$exp = (($rknumhigh & 0x7FF00000) >> 20) - 1023;
		$mantissa = (0x100000 | ($rknumhigh & 0x000FFFFF));
		$mantissalow1 = ($rknumlow & self::HIGH_ORDER_BIT) >> 31;
		$mantissalow2 = ($rknumlow & 0x7FFFFFFF);
		$value = $mantissa / 2 ** (20 - $exp);

		if ($mantissalow1 != 0) {
			$value += 1 / 2 ** (21 - $exp);
		}

		if ($mantissalow2 != 0) {
			$value += $mantissalow2 / 2 ** (52 - $exp);
		}
		if ($sign) {
			$value *= -1;
		}

		return $value;
	}

	/**
				 * @return float|int
				 */
				protected static function getIEEE754(int $rknum)
	{
		if (($rknum & 0x02) != 0) {
			$value = $rknum >> 2;
		} else {
			// changes by mmp, info on IEEE754 encoding from
			// research.microsoft.com/~hollasch/cgindex/coding/ieeefloat.html
			// The RK format calls for using only the most significant 30 bits
			// of the 64 bit floating point value. The other 34 bits are assumed
			// to be 0 so we use the upper 30 bits of $rknum as follows...
			$sign = ($rknum & self::HIGH_ORDER_BIT) >> 31;
			$exp = ($rknum & 0x7FF00000) >> 20;
			$mantissa = (0x100000 | ($rknum & 0x000FFFFC));
			$value = $mantissa / 2 ** (20 - ($exp - 1023));
			if ($sign) {
				$value = -1 * $value;
			}
			//end of changes by mmp
		}
		if (($rknum & 0x01) != 0) {
			$value /= 100;
		}

		return $value;
	}

	/**
	 * Get UTF-8 string from (compressed or uncompressed) UTF-16 string.
	 */
	protected static function encodeUTF16(string $string, bool $compressed = false): string
	{
		if ($compressed) {
			$string = self::uncompressByteString($string);
		}

		return StringHelper::convertEncoding($string, 'UTF-8', 'UTF-16LE');
	}

	/**
	 * Convert UTF-16 string in compressed notation to uncompressed form. Only used for BIFF8.
	 */
	protected static function uncompressByteString(string $string): string
	{
		$uncompressedString = '';
		$strLen = strlen($string);
		for ($i = 0; $i < $strLen; ++$i) {
			$uncompressedString .= $string[$i] . "\0";
		}

		return $uncompressedString;
	}

	/**
	 * Convert string to UTF-8. Only used for BIFF5.
	 */
	protected function decodeCodepage(string $string): string
	{
		return StringHelper::convertEncoding($string, 'UTF-8', $this->codepage);
	}

	/**
	 * Read 16-bit unsigned integer.
	 */
	public static function getUInt2d(string $data, int $pos): int
	{
		return ord($data[$pos]) | (ord($data[$pos + 1]) << 8);
	}

	/**
	 * Read 16-bit signed integer.
	 */
	public static function getInt2d(string $data, int $pos): int
	{
		return unpack('s', $data[$pos] . $data[$pos + 1])[1]; // @phpstan-ignore-line
	}

	/**
	 * Read 32-bit signed integer.
	 */
	public static function getInt4d(string $data, int $pos): int
	{
		// FIX: represent numbers correctly on 64-bit system
		// http://sourceforge.net/tracker/index.php?func=detail&aid=1487372&group_id=99160&atid=623334
		// Changed by Andreas Rehm 2006 to ensure correct result of the <<24 block on 32 and 64bit systems
		$_or_24 = ord($data[$pos + 3]);
		if ($_or_24 >= 128) {
			// negative number
			$_ord_24 = -abs((256 - $_or_24) << 24);
		} else {
			$_ord_24 = ($_or_24 & 127) << 24;
		}

		return ord($data[$pos]) | (ord($data[$pos + 1]) << 8) | (ord($data[$pos + 2]) << 16) | $_ord_24;
	}
}
