<?php

/*
 * This file is part of the Superdesk Web Publisher Core Bundle.
 *
 * Copyright 2016 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\CoreBundle\DependencyInjection\Compiler;

use SWP\Bundle\CoreBundle\Resolver\AssetLocationResolver;
use SWP\Bundle\CoreBundle\Resolver\AuthorAssetLocationResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class OverrideAssetLocationResolver extends AbstractOverridePass
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $assetLocationResolverDefinition = $this->getDefinitionIfExists($container, 'swp.resolver.asset_location');
        if (null !== $assetLocationResolverDefinition) {
            $assetLocationResolverDefinition
                ->setClass(AssetLocationResolver::class)
                ->addMethodCall('setTenantContext', [new Reference('swp_multi_tenancy.tenant_context')]);

            $authorAssetLocationResolverDefinition = new Definition('swp.resolver.author_asset_location');
            $authorAssetLocationResolverDefinition
                ->setClass(AuthorAssetLocationResolver::class)
                ->setArguments($assetLocationResolverDefinition->getArguments())
                ->addMethodCall('setTenantContext', [new Reference('swp_multi_tenancy.tenant_context')])
                ->setPublic(true)
            ;
        }
    }
}
