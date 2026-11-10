# **Enum** :

Enum stand for enumeration and used for same value which is already have like Product have 5 values p ="one",p2="Tow" i give the value string to porduct when user put P2 its false error because they put p capital that a main enum defination

----------------------------------

# ***array_column** *: 

Ka mtlb hota hai k array mai say column khinch lyna like

ya array hai $log

$log = [
    ['naam' => 'Ali',  'umar' => 20],
    ['naam' => 'Sara', 'umar' => 22],
];

es mai jb hm array_column lgy gy tu wo 2 row mai say 1 colmun khicnch ly ge 

## **like syntex first:**

array_column($array,"kis column ko khinchna hai")

array_column($log, 'naam')  ->  [0] => Ali     [1] => Sara
array_column($log, 'umar')  ->  [0] => 20      [1] => 22

---



# **In_array:**

Koi cheez array mai majood hai yan nii wo check krna


# **Array_filter:**

array_filter use for short the array like same sreach 

## **Syntax:**

in_array($dhoondo, $kahan_dhoondo, strict: true)

$fruits = ["banana","mango","anar"]

in_array("mango",$fruits,true)


# Can,Is,His:

that words use for questions to the developer like isVisibleShoper() same canAuthenticat

# Self:

Self ka mtlb hai k 1 class mai class koos method mai use krna like ya hum ny enum file bnai os mai ya rkha


```php
enum ProductStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case LIVE = 'live';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';

//ab agr mai es class ko agr mai method mai call kron tu mujy name ki zror nii ory ge like ProductStatus::Value mai sida self lgao ga or valu nikal long ga like slef::value
    }
```
