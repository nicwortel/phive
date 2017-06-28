<?php
namespace PharIo\Phive;

use PharIo\FileSystem\Filename;
use PharIo\Version\AndVersionConstraintGroup;
use PharIo\Version\AnyVersionConstraint;
use PharIo\Version\GreaterThanOrEqualToVersionConstraint;
use PharIo\Version\SpecificMajorVersionConstraint;
use PharIo\Version\Version;
use PharIo\Version\VersionConstraint;

class InstallService {

    /**
     * @var PhiveXmlConfig
     */
    private $phiveXml;

    /**
     * @var PharInstaller
     */
    private $installer;

    /**
     * @var PharRegistry
     */
    private $registry;

    /**
     * @var PharService
     */
    private $pharService;

    /**
     * @var CompatibilityChecker
     */
    private $compatibilityChecker;

    /**
     * @param PhiveXmlConfig       $phiveXml
     * @param PharInstaller        $installer
     * @param PharRegistry         $registry
     * @param PharService          $pharService
     * @param CompatibilityChecker $compatibilityChecker
     */
    public function __construct(
        PhiveXmlConfig $phiveXml,
        PharInstaller $installer,
        PharRegistry $registry,
        PharService $pharService,
        CompatibilityChecker $compatibilityChecker
    ) {
        $this->phiveXml             = $phiveXml;
        $this->installer            = $installer;
        $this->registry             = $registry;
        $this->pharService          = $pharService;
        $this->compatibilityChecker = $compatibilityChecker;
    }

    /**
     * @param Release $release
     * @param VersionConstraint $versionConstraint
     * @param Filename $destination
     * @param bool $makeCopy
     */
    public function execute(Release $release, VersionConstraint $versionConstraint, Filename $destination, $makeCopy) {
        $phar = $this->pharService->getPharFromRelease($release);
        if ($phar->hasManifest()) {
            $result = $this->compatibilityChecker->checkCompatibility($phar);
            if (!$result->isCompatible()) {
                throw new \RuntimeException(
                    "Your Environment is not capable to use this phar.\n\n" . $result->asString()
                );
            }
        }

        $this->installer->install($phar->getFile(), $destination, $makeCopy);
        $this->registry->addUsage($phar, $destination);

        if ($this->phiveXml->hasConfiguredPhar($release->getName(), $release->getVersion())) {
            $configuredPhar = $this->phiveXml->getConfiguredPhar($release->getName(), $release->getVersion());
            if ($configuredPhar->getVersionConstraint()->asString() === $versionConstraint->asString()) {
                return;
            }
        }

        $this->phiveXml->addPhar(
            new InstalledPhar(
                $phar->getName(),
                $release->getVersion(),
                $this->getInstalledVersionConstraint($versionConstraint, $release->getVersion()),
                $destination,
                $makeCopy
            )
        );
    }

    /**
     * @param VersionConstraint $requestedVersionConstraint
     * @param Version $installedVersion
     *
     * @return VersionConstraint
     */
    private function getInstalledVersionConstraint(VersionConstraint $requestedVersionConstraint, Version $installedVersion) {
        if (!$requestedVersionConstraint instanceof AnyVersionConstraint) {
            return $requestedVersionConstraint;
        }
        return new AndVersionConstraintGroup(
            sprintf('^%s', $installedVersion->getVersionString()),
            [
                new GreaterThanOrEqualToVersionConstraint($installedVersion->getVersionString(), $installedVersion),
                new SpecificMajorVersionConstraint($installedVersion->getVersionString(), $installedVersion->getMajor()->getValue())
            ]
        );
    }

}
