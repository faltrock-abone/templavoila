<?php
namespace Extension\Templavoila\Hooks;

use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;

class WizardItems implements NewContentElementWizardHookInterface
{
    /**
     * Processes the items of the new content element wizard
     * and inserts necessary default values for items created within a grid
     *
     * @param array $wizardItems The array containing the current status of the wizard item list before rendering
     * @param NewContentElementController $parentObject The parent object that triggered this hook
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject)
    {
        $addingItems = [
            'fce' => [
                'header' => $this->getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/BackendLayout.xlf:fce'),
            ],
        ];
        $apiObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Extension\Templavoila\Service\ApiService::class);

        // Flexible content elements:
        $positionPid = $parentObject->id;
        $storageFolderPID = $apiObj->getStorageFolderPid($positionPid);

        $toRepo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);
        $toList = $toRepo->getTemplatesByStoragePidAndScope($storageFolderPID, \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_FCE);
        foreach ($toList as $toObj) {
            /** @var \Extension\Templavoila\Domain\Model\Template $toObj */
            if ($toObj->isPermittedForUser()) {
                $tmpFilename = $toObj->getIcon();
                $addingItems['fce_' . $toObj->getKey()] = [
                    'icon' => (@is_file(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(substr($tmpFilename, 3)))) ? $tmpFilename : ('../' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('templavoila') . 'Resources/Public/Image/default_previewicon.gif'),
                    'description' => $toObj->getDescription() ? $this->getLanguageService()->sL($toObj->getDescription()) : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('template_nodescriptionavailable'),
                    'title' => $toObj->getLabel(),
                    'params' => $this->getDsDefaultValues($toObj)
                ];
            }
        }

        // Insert FCE area before forms or plugins or at last.
        $key_indices = array_flip(array_keys($wizardItems));
        if (isset($wizardItems['forms'])) {
            $offset = $key_indices['forms'];
        } elseif (isset($wizardItems['plugins'])) {
            $offset = $key_indices['plugins'];
        } else {
            $offset = -1;
        }
        $wizardItems = array_slice($wizardItems, 0, $offset, true) + $addingItems + array_slice($wizardItems, $offset, null, true);
    }

    /**
     * Process the default-value settings
     *
     * @param \Extension\Templavoila\Domain\Model\Template $toObj LocalProcessing as array
     *
     * @return string additional URL arguments with configured default values
     */
    public function getDsDefaultValues(\Extension\Templavoila\Domain\Model\Template $toObj)
    {
        $dsStructure = $toObj->getLocalDataprotArray();

        $dsValues = '&defVals[tt_content][CType]=templavoila_pi1'
                . '&defVals[tt_content][tx_templavoila_ds]=' . $toObj->getDatastructure()->getKey()
                . '&defVals[tt_content][tx_templavoila_to]=' . $toObj->getKey();

        if (is_array($dsStructure) && is_array($dsStructure['meta']['default']['TCEForms'])) {
                foreach ($dsStructure['meta']['default']['TCEForms'] as $field => $value) {
                        $dsValues .= '&defVals[tt_content][' . $field . ']=' . $value;
                }
        }

        return $dsValues;
    }


    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
