<?php

declare(strict_types=1);

namespace App\Domain\Catalog\ValueObjects;

use InvalidArgumentException;
use Stringable;

/*
|--------------------------------------------------------------------------
| YE FILE KYA HAI
|--------------------------------------------------------------------------
| SKU = Stock Keeping Unit. Seller ka APNA product code, jaise
| ZML-LAWN-0431. Platform ye code nahi banata, seller deta hai — kyunki
| seller ki apni register aur uske gatte par wohi code chipka hota hai.
|
| Aham baat: SKU product ka nahi, VARIANT ka hota hai. "Noor Lawn 3-Piece
| Suit" ek product hai, lekin Blue/M aur Red/L alag alag bikte hain aur
| unka stock alag ginna parta hai — to har variant ka apna SKU hota hai.
| Isi liye `sku` column product_variants mein hai, products mein nahi.
|
| Ye class ek "value object" hai: ek chhoti class jiska poora kaam ek
| qeemat ko sahi shakal mein sambhalna hai. Database se baat nahi karti,
| kisi service ko nahi bulati, aur banne ke baad badalti nahi.
|
|--------------------------------------------------------------------------
| KU BANAI - ISKI ZAROORAT KU PARI
|--------------------------------------------------------------------------
| Taake guarantee ek jagah likhi ho. Jahan bhi `Sku` type nazar aaye,
| wahan dobara check karne ki zaroorat nahi — jo Sku object ban gaya,
| uski shakal zaroor sahi hai, warna wo ban hi nahi pata.
|
| Agar iski jagah seedhi `string` ghoomti, to har service mein wohi chaar
| check dobara likhne parte — product form mein, CSV import mein, API
| mein — aur ek din kisi ek jagah bhool jate. Wohi ek jagah wo darwaza
| ban jati jahan se gandi value andar aa jati.
|
|--------------------------------------------------------------------------
| KAHAN KAHAN ZAROORAT PAREGI
|--------------------------------------------------------------------------
| - product_variants table ka `sku` column                      (C6)
| - seller ka product form / VariantService                     (C16)
| - CSV bulk import ki har row, aur uski error report           (C13, D-CAT-8)
| - seller ke apne variants dhoondna                            (C17)
|
|--------------------------------------------------------------------------
| AGAR YE FILE NA HOTI
|--------------------------------------------------------------------------
| Do haal hote. Ek: "zml-0431" aur "ZML-0431" do ALAG SKU gine jate,
| kyunki unique(seller_profile_id, sku) database mein inhein do maanta
| hai — aur ek hi product ki do rows ban jatin. Do: gandi value (space
| wali, ya ghalat harf wali) database tak pohnch jati, aur baad mein use
| saaf karna namumkin ho jata — kyunki tab tak order usi SKU par lag
| chuke hote.
|
|--------------------------------------------------------------------------
| KIS FILE SE JURI HAI
|--------------------------------------------------------------------------
| ProductVariant model      <- `sku` column ka cast is class par
| migration (C6)            <- wahan string('sku', 64), aur 64 ki hadd
|                              DONO jagah ek jaisi honi chahiye
| unique(seller_profile_id, sku)  <- uniqueness DATABASE ka kaam hai,
|                                   is file ka nahi (section 5)
*/
final readonly class Sku implements Stringable
{
    public function __construct(private string $code)
    {
        if ($code === '') {
            throw new InvalidArgumentException('SKU cannot be empty.');
        }

        if (strlen($code) < 3) {
            throw new InvalidArgumentException(
                'SKU must be at least 3 characters, got '.strlen($code).'.',
            );
        }

        if (strlen($code) > 64) {
            throw new InvalidArgumentException(
                'SKU must be at most 64 characters, got '.strlen($code).'.',
            );
        }

        if (preg_match('/^[A-Z0-9_-]+$/', $code) !== 1) {
            throw new InvalidArgumentException(
                'SKU may only contain A-Z, 0-9, dash and underscore. Got: '.$code,
            );
        }
    }

    /*
    | SAFAI SIRF YAHAN HOTI HAI, CONSTRUCTOR MEIN NAHI.
    | Bahar se aane wali har string isi darwaze se guzregi — form, CSV, API.
    | Constructor khud trim karta to `new Sku(" abc ")` chup chaap chal jata
    | aur kabhi pata na chalta ke kahin se gandi value aa rahi hai. Do alag
    | method ka matlab do baatein saaf likhi hui: "yahan bahar ka maal hai"
    | aur "yahan saaf maal hai".
    |
    | Upper case karna lazmi hai, reject karna nahi: `zml-0431` likhne wala
    | seller ghalat nahi, uski aadat hai. Lekin `zml-0431` aur `ZML-0431` ko
    | DO alag SKU maan lena asli masla hai, kyunki
    | unique(seller_profile_id, sku) unhein do maanta hai aur ek hi product
    | ki do rows ban jati hain.
    */
    public static function fromUserInput(string $raw): self
    {
        return new self(strtoupper(trim($raw)));
    }

    public function code(): string
    {
        return $this->code;
    }

    /*
    | `$a === $b` YAHAN KAAM NAHI KARTA, aur ye enum se asli farq hai.
    | Objects par === poochta hai "kya ye bilkul WOHI object hai", na ke
    | "kya andar ek jaisa hai" — to do dafa `new Sku('ABC')` do alag object
    | dete hain aur === false deta hai. Enum case poore program mein ek hi
    | object hota hai, isi liye ProductStatus mein === chalta tha.
    | Isliye yahan andar ka code compare hota hai.
    |
    | Aur yahan exception NAHI, kyunki "kya ye do SKU ek hain" ka jawab
    | "nahi" hai — wo ghalati nahi, jawab hai.
    */
    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
