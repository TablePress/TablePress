<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Shared\OLE;

/**
 * An in-memory node used while constructing a CFB directory sibling tree.
 *
 * @internal
 */
final class PPSTreeNode
{
	public PPS $pps;
				public ?self $left = null;

	public ?self $right = null;

	public int $color = 0;

	public function __construct(PPS $pps)
				{
					$this->pps = $pps;
				}
}
