<?php
declare(strict_types=1);
namespace IchHabRecht\ContentDefender\Form\FormDataProvider;

/*
 * This file is part of the TYPO3 extension content_defender.
 *
 * (c) Nicole Cordes <typo3@cordes.co>
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use IchHabRecht\ContentDefender\BackendLayout\BackendLayoutConfiguration;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaCTypeItems implements FormDataProviderInterface
{
    /**
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if ('tt_content' !== $result['tableName']) {
            return $result;
        }

        $pageId = !empty($result['effectivePid']) ? (int)$result['effectivePid'] : (int)$result['databaseRow']['pid'];
        $backendLayoutConfiguration = BackendLayoutConfiguration::createFromPageId($pageId);

        $colPos = (int)$result['databaseRow']['colPos'];
        $columnConfiguration = $backendLayoutConfiguration->getConfigurationByColPos($colPos);
        if (empty($columnConfiguration) || (empty($columnConfiguration['allowed.']) && empty($columnConfiguration['disallowed.']))) {
            return $result;
        }

        $allowedConfiguration = $columnConfiguration['allowed.'] ?? [];
        foreach ($allowedConfiguration as $field => $value) {
            if (empty($result['processedTca']['columns'][$field]['config']['items'])) {
                continue;
            }

            $allowedValues = GeneralUtility::trimExplode(',', $value);
            $result['processedTca']['columns'][$field]['config']['items'] = array_filter(
                $result['processedTca']['columns'][$field]['config']['items'],
                function ($item) use ($allowedValues) {
                    return in_array($item[1], $allowedValues);
                }
            );
        }

        $disallowedConfiguration = $columnConfiguration['disallowed.'] ?? [];
        foreach ($disallowedConfiguration as $field => $value) {
            if (empty($result['processedTca']['columns'][$field]['config']['items'])) {
                continue;
            }

            $disAllowedValues = GeneralUtility::trimExplode(',', $value);
            $result['processedTca']['columns'][$field]['config']['items'] = array_filter(
                $result['processedTca']['columns'][$field]['config']['items'],
                function ($item) use ($disAllowedValues) {
                    return !in_array($item[1], $disAllowedValues);
                }
            );
        }

        return $result;
    }
}
