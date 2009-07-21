<?php

class mymb {
	private $data;
	private $encoding;

	public function create($str, $encoding = NULL) {
		return new mymb($str, $encoding != NULL || ! isset($this) ? $encoding : $this->encoding);
	}

	public function __construct($mbStr) {
		$this->encoding = 'utf-8';

		$len = iconv_strlen($mbStr, $this->encoding);

		if ($len == 0)
			$this->data = array();
		else {
			$this->data = array_fill(0, $len, ' ');
			for($i = 0; $i<$len; $i++)
				$this->data[$i] = iconv_substr($mbStr, $i, 1, $this->encoding);
		}
	}

	public function getMultyByte() { return implode($this->data); }

	public function strlen() { return count($this->data); }               

	public function append($str) {
//		echo 'append: '.print_r($str, true)."\n";
		if (is_object($str))
			foreach($str->data as $c)
				$this->data[] = $c;
		else
			$this->data[] = $str;

		return $this;
	}

	public function substr($from, $length = NULL) {
		$res = $this->create('');
		$res->data = array_slice($this->data, $from, 
			$length ? $length : ($this->strlen() - $from));
		return $res;
	}

	public function c($idx) {
		return $this->data[$idx];
	}

	public function s($idx, $c) {
		$this->data[$idx] = $c;
	}


static private function LCQ($old, $new, $oldFrom = 0, $newFrom = 0, $oldTo = NULL, $newTo = NULL) {
//	global $debug;
//	$s = getmicrotime();
	if ($oldTo === NULL)
		$oldTo = $old->strlen();
	if ($newTo === NULL)
		$newTo = $new->strlen();
	$maxLen = 0;
	$lcq = NULL;

//	if ($debug)
//		echo 'LCQ ['.$old.'] ['.$new.'] '.$oldFrom.', '.$newFrom.', '.$oldTo.', '.$newTo."\n";

	for($oldIdx = $oldFrom; $oldIdx < $oldTo; $oldIdx++) {
		for($newIdx = $newFrom; $newIdx < $newTo && $oldIdx < $oldTo; $newIdx++) {
			if($old->c($oldIdx) == $new->c($newIdx)) {
				for($equalLen = 1;
					$newIdx + $equalLen < $newTo && $oldIdx + $equalLen < $oldTo
						&& $old->c($oldIdx + $equalLen) == $new->c($newIdx + $equalLen); 
					$equalLen++);
				if ($equalLen > $maxLen) {
					$lcq = array(
						$oldIdx, 
						$newIdx, 
						$oldIdx + $equalLen < $oldTo,
						$newIdx + $equalLen < $newTo,
						$equalLen);
					$maxLen = $equalLen;

					$newIdx += $maxLen;
					$oldIdx += $maxLen;
				}
			}
		}
	}

//	$e = getmicrotime(); echo sprintf("%.3f\n", ($e-$s));

	return $lcq;
}

public function mb_diff($new) {
//	global $debug;
//	if ($debug)
//		echo 'diff ['.$old.'] ['.$new."]\n";
	$old = $this;

	$oldLen = $old->strlen();
	$newLen = $new->strlen();

	if ($oldLen == 0 && $newLen == 0)
		return $this->create('');
	else if ($oldLen == 1 && $newLen == 1) {
		if ($old == $new)
			return $new;
		else {
			return $this->create('<ins>')->append($new)->append('</ins>');
		}
	} else if ($oldLen == 0 && $newLen > 0)
		return $this->create('<ins>')->append($new)->append('</ins>');
	else if ($oldLen > 0 && $newLen == 0)
		return $this->create('<del>')->append($old)->append('</del>');

	// 1. find LCQ
	$lcq = mymb::LCQ($old, $new);
//	if ($debug)
//		echo 'LCQ: '.print_r($lcq, true)."\n";
	if ($lcq === NULL || ($lcq[4] <= $lcq[0] && $lcq[4] <= $lcq[1] && $lcq[4] < 10))
		return $this->create('<ins>')->append($new)->append('</ins>');

		
	$result = $this->create('');

	// 2. evaluate diff on part before LCQ
	if ($lcq[0] > 0 && $lcq[1] == 0)
		$result = $result->append('<del>')->append($old->substr(0, $lcq[0]))->append('</del>');
	else if ($lcq[0] == 0 && $lcq[1] > 0)
		$result = $result->append('<ins>')->append($new->substr(0, $lcq[1]))->append('</ins>');
	else if ($lcq[0] > 0 && $lcq[1] > 0)
		$result = $result->append($old->substr(0, $lcq[0])->mb_diff($new->substr(0, $lcq[1])));

	$result = $result->append($new->substr($lcq[1], $lcq[4]));

	// 3. evaluate diff on part after LCQ
	if ($lcq[2] && ! $lcq[3])
		$result = $result->append('<del>')->append($old->substr($lcq[0] + $lcq[4]))->append('</del>');
	else if (! $lcq[2] && $lcq[3])
		$result = $result->append('<ins>')->append($new->substr($lcq[1] + $lcq[4]))->append('</ins>');
	else if ($lcq[2] && $lcq[3])
		$result = $result->append($old->substr(
			$lcq[0] + $lcq[4])->mb_diff($new->substr($lcq[1] + $lcq[4])));

	return $result;	
}

	public function diff($new) {
		return $this->mb_diff(new mymb($new))->getMultyByte();
	}
}


function diff($old, $new) {
	return mymb::create($old)->diff(mymb::create($new));
}

//$myStr = new mymb(iconv('windows-1251', 'utf-8', '����!'));
//print_r($myStr);
//echo "strlen: ".$myStr->strlen()."\n";
//echo "substr(2, 3): ".print_r($myStr->substr(2, 3), true)."\n";

return 0;


function assertDiffEqual($expected, $str1, $str2, $msg) {
	$str1 = new mymb(iconv('windows-1251', 'utf-8', $str1));
	$str2 = iconv('windows-1251', 'utf-8', $str2);
	
	$have = iconv('utf-8', 'windows-1251', $str1->diff($str2));

	if (strcmp($expected, $have) != 0) {
		echo ("\n[$msg] Failed!\nHave: \n$have\nExpected:\n$expected\n");
	} else
		echo ("\n[$msg] Passed!\n");
}

function getmicrotime() {
	list($nano, $sec) = explode(' ', microtime());
	return $sec+$nano;
}

function xmlDiffCDATA($strOriginal, $strNew, $el) {
	$doc = $el->ownerDocument;

	$cdata = diff($strOriginal, $strNew);
	$el->appendChild($doc->createCDATASection($cdata));
}

	$debug = 0;

//return;
	$str1 = 'Yandex';
	$str2 = 'Goldcorp';
	$expected = '<ins>Goldcorp</ins>';
	assertDiffEqual($expected, $str1, $str2, 1);

	$str1 = '�,';
	$str2 = '�';
	$expected = '�<del>,</del>';
	assertDiffEqual($expected, $str1, $str2, 2);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
//           0         0         0         0         0         0         0         0         0         0         0
//																								                 0         0         0         0         0         0         0         0         0         0         0
//                                                                                                                                                                                                                   0         0         0         0         0         0         0         0         0         0         0
//                                                                                                                                                                                                                                                                                                                       0         0         0         0
	$str2 = '������� ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
//	print_r(LCQ($str1, $str2));
	$expected = '�������<del>,</del> ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	assertDiffEqual($expected, $str1, $str2, 3);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	$str2 = '�������, �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
//	print_r(LCQ($str1, $str2));
	$expected = '�������,<del> �������</del> �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	assertDiffEqual($expected, $str1, $str2, 4);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	$str2 = '�������, ����� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
//	print_r(LCQ($str1, $str2));
	$expected = '�������, <ins>��</ins>��� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	assertDiffEqual($expected, $str1, $str2, 5);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	$str2 = '�������, ������� �������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
//	print_r(LCQ($str1, $str2));
	$expected = '�������, ������� ������<del>�� ��� ������ �������� �����</del>�� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	assertDiffEqual($expected, $str1, $str2, 6);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	$str2 = '�������, ������� �������� ���, ����.';
//	print_r(LCQ($str1, $str2));
	$expected = '�������, ������� �������� �<del>�� ������ �������� ������� �</del>��, <ins>����.</ins>';
	assertDiffEqual($expected, $str1, $str2, 7);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	$str2 = '�������, ������� �������� ���, ����';
//	print_r(LCQ($str1, $str2));
	$expected = '�������, ������� �������� �<del>�� ������ �������� ������� �</del>��, <ins>����</ins>';
	assertDiffEqual($expected, $str1, $str2, 8);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	$str2 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
//	print_r(LCQ($str1, $str2));
	$expected = '<ins>�������</ins>, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	assertDiffEqual($expected, $str1, $str2, 9);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	$str2 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
//	print_r(LCQ($str1, $str2));
	$expected = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� Goldcorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	assertDiffEqual($expected, $str1, $str2, 10);

	$str1 = '��������� � ����� Peer Production';
	$str2 = '��������� � ����� Peer Production 2';
//	print_r(LCQ($str1, $str2));
	$expected = '��������� � ����� Peer Production<ins> 2</ins>';
	assertDiffEqual($expected, $str1, $str2, 11);

	$str1 = '��������� � ����� Peer Production 2';
	$str2 = '��������� � ����� Peer Production';
//	print_r(LCQ($str1, $str2));
	$expected = '��������� � ����� Peer Production<del> 2</del>';
	assertDiffEqual($expected, $str1, $str2, 12);

	$str1 = '��������� � ����� Peer Production';
	$str2 = '2 ��������� � ����� Peer Production';
//	print_r(LCQ($str1, $str2));
	$expected = '<ins>2 </ins>��������� � ����� Peer Production';
	assertDiffEqual($expected, $str1, $str2, 13);

	$str1 = '��������� � ����� Peer Production';
	$str2 = '��������� Peer Production';
//	print_r(LCQ($str1, $str2));
	$expected = '���������<del> � �����</del> Peer Production';
	assertDiffEqual($expected, $str1, $str2, 14);

	$str1 = '��������� ��������, ������������� � ������� � ������ ������� ����� � �������, ��������� ����������� � ������������, ������������� ������� � ����������� �������� ����������������� ����������, ������� ��������� �������� ���������� ������ � ��������. �������� ������� ������ ���� ������� �������������������. ����� ������ ����������, � ����������� ���������� ������������, ��� ������ � ��� ����, �������, ����������������� ��������� � ������� ��������� ���������� ���, ������ � ���������. ����� �� ���������� ������� ��� ����� ������� ������, � ��������, ��� ������, � ������ � ��� � ���� ��������, ������ � ��������.';
	$str2 = '��������� ��������, ������������� � �������, � ������ ������� ����� � �������, ��������� ����������� � ������������, ������������� ������� � ����������� �������� ����������������� ����������, ������� ��������� �� ���������� ������ � ��������. �������� ������� ������ ���� ������� �������������������. ����� ������ ����������, � ����������� ���������� ������������, ��� ������ � ��� ����, �������, ����������������� ��������� � ������� ��������� ���������� ���, ������ � ���������. ����� �� ���������� ������� ��� ����� ������� ������, � ��������, ��� ������, � ������ � ��� � ���� ��������, ������ � ��������.';
//	print_r(LCQ($str1, $str2));
	$expected = '��������� ��������, ������������� � �������<ins>,</ins> � ������ ������� ����� � �������, ��������� ����������� � ������������, ������������� ������� � ����������� �������� ����������������� ����������, ������� ��������� <ins>��</ins> ���������� ������ � ��������. �������� ������� ������ ���� ������� �������������������. ����� ������ ����������, � ����������� ���������� ������������, ��� ������ � ��� ����, �������, ����������������� ��������� � ������� ��������� ���������� ���, ������ � ���������. ����� �� ���������� ������� ��� ����� ������� ������, � ��������, ��� ������, � ������ � ��� � ���� ��������, ������ � ��������.';
	assertDiffEqual($expected, $str1, $str2, 15);

	$str1 = '��������� ��������, ������������� � �������, � ������ ������� ����� � �������, ��������� ����������� � ������������, ������������� ������� � ����������� �������� ����������������� ����������, ������� ��������� �� ���������� ������ � ��������. �������� ������� ������ ���� ������� �������������������. ����� ������ ����������, � ����������� ���������� ������������, ��� ������ � ��� ����, �������, ����������������� ��������� � ������� ��������� ���������� ���, ������ � ���������. ����� �� ���������� ������� ��� ����� ������� ������, � ��������, ��� ������, � ������ � ��� � ���� ��������, ������ � ��������.';
	$str2 = '��������� ��������, ������������� � ������� � ������ ������� ����� � �������, ��������� ����������� � ������������, ������������� ������� � ����������� �������� ����������������� ����������, ������� ��������� �������� ���������� ������ � ��������. �������� ������� ������ ���� ������� �������������������. ����� ������ ����������, � ����������� ���������� ������������, ��� ������ � ��� ����, �������, ����������������� ��������� � ������� ��������� ���������� ���, ������ � ���������. ����� �� ���������� ������� ��� ����� ������� ������, � ��������, ��� ������, � ������ � ��� � ���� ��������, ������ � ��������.';
//	print_r(LCQ($str1, $str2));
	$expected = '��������� ��������, ������������� � �������<del>,</del> � ������ ������� ����� � �������, ��������� ����������� � ������������, ������������� ������� � ����������� �������� ����������������� ����������, ������� ��������� <ins>��������</ins> ���������� ������ � ��������. �������� ������� ������ ���� ������� �������������������. ����� ������ ����������, � ����������� ���������� ������������, ��� ������ � ��� ����, �������, ����������������� ��������� � ������� ��������� ���������� ���, ������ � ���������. ����� �� ���������� ������� ��� ����� ������� ������, � ��������, ��� ������, � ������ � ��� � ���� ��������, ������ � ��������.';
	assertDiffEqual($expected, $str1, $str2, 16);

	$str1 = '�������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� GoldCorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	$str2 = '�������, �������, �������, ������� �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� GoldCorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
//	print_r(LCQ($str1, $str2));
	$expected = '�������, �������<ins>, �������, �������</ins> �������� ��� ������ �������� ������� ���, ��� �������, ������������ �������� GoldCorp Inc., ����� �� ����� ����� � ���� ���������, ����������� ��������� - �������������� ������������� ��������. �������, ������� �� ������ ��� �� ��������, ���� ������. ����� ����, ��� ���� �������, � ������� � ������ ��������� ���� �������������.';
	assertDiffEqual($expected, $str1, $str2, 17);

	$str1 = '���� ����� - ��� �������������� ����� ��� ��� ������� �������� � ������ �������� - ���, � ������� ��� ����� ����������� � ������������ � �������� ����������� ��������� � ������ ������� ������������� ������ ���, ��� ��� ���� ���� ���������� ���� ����������� ������. ��� ������� �������� ���� ������� ��������� �������������� ���� ������ � �������� ����� ����������� �������� ������, �������� � �������� - � �� ���� ����� ����� ������ � �������� ���� ������������ ���������. ��� �������� � ����� ����� ��� �������� �������������� ���� ������, ������� �������������� � ��������� � ������� - ��� ��������� ���������� �������� ����� ����� ���������� � ������ ����� � ������������ ����� ������� �������������� ��������.';
	$str2 = '���� ����� - ��� �������������� ����� ��� ��� ������� �������� � ������ �������� - ���, � ������� ��� ����� ����������� � ������������ � �������� ����������� ��������� � ������ ������� ������������� ������ ���, ��� ��� ���� ���� ���������� ���� ����������� ������. ��� ������� �������� ���� ������� ��������� �������������� ���� ������ � �������� ����� ����������� �������� ������, �������� � ��������, � �� ���� ����� ����� ������ � �������� ���� ������������ ���������. ��� �������� � ����� ����� ��� �������� �������������� ���� ������, ������� �������������� � ��������� � ������� - ��� ��������� ���������� �������� ����� ����� ���������� � ������ ����� � ������������ ����� ������� �������������� ��������.';
//	print_r(LCQ($str1, $str2));
	$expected = '���� ����� - ��� �������������� ����� ��� ��� ������� �������� � ������ �������� - ���, � ������� ��� ����� ����������� � ������������ � �������� ����������� ��������� � ������ ������� ������������� ������ ���, ��� ��� ���� ���� ���������� ���� ����������� ������. ��� ������� �������� ���� ������� ��������� �������������� ���� ������ � �������� ����� ����������� �������� ������, �������� � ��������<ins>,</ins> � �� ���� ����� ����� ������ � �������� ���� ������������ ���������. ��� �������� � ����� ����� ��� �������� �������������� ���� ������, ������� �������������� � ��������� � ������� - ��� ��������� ���������� �������� ����� ����� ���������� � ������ ����� � ������������ ����� ������� �������������� ��������.';
	assertDiffEqual($expected, $str1, $str2, 18);

	$str1 = '���� ����� - ��� �������������� ����� ��� ��� ������� �������� � ������ �������� - ���, � ������� ��� ����� ����������� � ������������ � �������� ����������� ��������� � ������ ������� ������������� ������ ���, ��� ��� ���� ���� ���������� ���� ����������� ������. ��� ������� �������� ���� ������� ��������� �������������� ���� ������ � �������� ����� ����������� �������� ������, �������� � ��������, � �� ���� ����� ����� ������ � �������� ���� ������������ ���������. ��� �������� � ����� ����� ��� �������� �������������� ���� ������, ������� �������������� � ��������� � ������� - ��� ��������� ���������� �������� ����� ����� ���������� � ������ ����� � ������������ ����� ������� �������������� ��������.';
	$str2 = '���� ����� - ��� �������������� ����� ��� ��� ������� �������� � ������ �������� - ���, � ������� ��� ����� ����������� � ������������ � �������� ����������� ��������� � ������ ������� ������������� ������ ���, ��� ��� ���� ���� ���������� ���� ����������� ������.  ��� ������� �������� ���� ������� ��������� �������������� ���� ������ � �������� ����� ����������� �������� ������, �������� � ��������, � �� ���� ����� ����� ������ � �������� ���� ������������ ���������. ��� �������� � ����� ����� ��� �������� �������������� ���� ������, ������� �������������� � ��������� � ������� - ��� ��������� ���������� �������� ����� ����� ���������� � ������ ����� � ������������ ����� ������� �������������� ��������.';
//	print_r(LCQ($str1, $str2));
	$expected = '���� ����� - ��� �������������� ����� ��� ��� ������� �������� � ������ �������� - ���, � ������� ��� ����� ����������� � ������������ � �������� ����������� ��������� � ������ ������� ������������� ������ ���, ��� ��� ���� ���� ���������� ���� ����������� ������. <ins> </ins>��� ������� �������� ���� ������� ��������� �������������� ���� ������ � �������� ����� ����������� �������� ������, �������� � ��������, � �� ���� ����� ����� ������ � �������� ���� ������������ ���������. ��� �������� � ����� ����� ��� �������� �������������� ���� ������, ������� �������������� � ��������� � ������� - ��� ��������� ���������� �������� ����� ����� ���������� � ������ ����� � ������������ ����� ������� �������������� ��������.';
	assertDiffEqual($expected, $str1, $str2, 19);

	$str1 = '-Steven L. Sheinheit, EVP � ������������ ������������ ����������, MetLife';
	$str2 = '-Steven L. Sheinheit, EVP � ������������ ������������ ����������, MetLife';
//           0         0          0          0          0          0          0
	$expected = '-Steven L. Sheinheit, EVP � ������������ <ins>�</ins>����������� <ins>�</ins>���������, MetLife';
//	print_r(LCQ($str1, $str2));
	assertDiffEqual($expected, $str1, $str2, 20);

	$str1 = '-Steven L. Sheinheit, EVP � ������������ ������������ ����������, MetLife';
	$str2 = '-Steven L. Sheinheit, EVP � ������������ ������������ ����������, MetLife';
//           0         0          0          0          0          0          0
	$expected = '-Steven L. Sheinheit, EVP � <ins>�</ins>����������� <ins>�</ins>����������� <ins>�</ins>���������, MetLife';
//	print_r(LCQ($str1, $str2));
	assertDiffEqual($expected, $str1, $str2, 21);
?>