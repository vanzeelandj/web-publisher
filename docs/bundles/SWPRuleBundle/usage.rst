Usage
=====

How to Create and Register Custom Rule Applicator
-------------------------------------------------

Rule applicators are used to apply given rule's configuration to an object.
Read more about it in the Rule component documentation (in the :doc:`/components/Rule/usage` section).

Creating the Rule Applicator Class
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A new Rule Applicator has to implement the ``SWP\Component\Rule\Applicator\RuleApplicatorInterface`` interface.

``ArticleRuleApplicator`` class example:

.. code-block:: php

    <?php

    namespace Acme\DemoBundle\Applicator;
    // ..
    use Psr\Log\LoggerInterface;
    use SWP\Bundle\ContentBundle\Model\ArticleInterface;
    use SWP\Bundle\ContentBundle\Provider\RouteProviderInterface;
    use SWP\Component\Rule\Applicator\RuleApplicatorInterface;
    use SWP\Component\Rule\Model\RuleSubjectInterface;
    use SWP\Component\Rule\Model\RuleInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    final class ArticleRuleApplicator implements RuleApplicatorInterface
    {
        /**
         * @var RouteProviderInterface
         */
        private $routeProvider;

        /**
         * @var LoggerInterface
         */
        private $logger;

        /**
         * @var array
         */
        private $supportedKeys = ['route', 'templateName'];

        /**
         * ArticleRuleApplicator constructor.
         *
         * @param RouteProviderInterface $routeProvider
         * @param LoggerInterface        $logger
         */
        public function __construct(RouteProviderInterface $routeProvider, LoggerInterface $logger)
        {
            $this->routeProvider = $routeProvider;
            $this->logger = $logger;
        }

        /**
         * {@inheritdoc}
         */
        public function apply(RuleInterface $rule, RuleSubjectInterface $subject)
        {
            $configuration = $this->validateRuleConfiguration($rule->getConfiguration());

            if (!$this->isAllowedType($subject) || empty($configuration)) {
                return;
            }

            /* @var ArticleInterface $subject */
            if (isset($configuration[$this->supportedKeys[0]])) {
                $route = $this->routeProvider->getOneById($configuration[$this->supportedKeys[0]]);

                if (null === $route) {
                    $this->logger->warning('Route not found! Make sure the rule defines an existing route!');

                    return;
                }

                $subject->setRoute($route);
            }

            $subject->setTemplateName($configuration[$this->supportedKeys[1]]);

            $this->logger->info(sprintf(
                'Configuration: "%s" for "%s" rule has been applied!',
                json_encode($configuration),
                $rule->getExpression()
            ));
        }

        /**
         * {@inheritdoc}
         */
        public function isSupported(RuleSubjectInterface $subject)
        {
            return $subject instanceof ArticleInterface && 'article' === $subject->getSubjectType();
        }

        private function validateRuleConfiguration(array $configuration)
        {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);

            try {
                return $resolver->resolve($configuration);
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }

            return [];
        }

        private function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults([$this->supportedKeys[1] => null]);
            $resolver->setDefined($this->supportedKeys[0]);
        }

        private function isAllowedType(RuleSubjectInterface $subject)
        {
            if (!$subject instanceof ArticleInterface) {
                $this->logger->warning(sprintf(
                    '"%s" is not supported by "%s" rule applicator!',
                    is_object($subject) ? get_class($subject) : gettype($subject),
                    get_class($this)
                ));

                return false;
            }

            return true;
        }
    }


Configuring the Rule Applicator
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To register your new rule applicator, simply add a definition to your services file and tag it with a special name: ``applicator.rule_applicator``, it will be automatically added to the chain of rule applicators:

.. code-block:: yaml

    # Resources/config/services.yml
    acme_my_custom_rule_applicator:
        class: 'Acme\DemoBundle\Applicator\ArticleRuleApplicator'
        arguments:
            - '@swp.provider.route'
            - '@logger'
        tags:
            - { name: applicator.rule_applicator }

How to Create and Enable Custom Rule Entity
-------------------------------------------

In some cases you would want to extend the default ``Rule`` model to add some extra properties etc.
To do this you need to create a custom class which extends the default one.

.. code-block:: php

   <?php
    // ..
    namespace Acme\DemoBundle\Entity;

    use SWP\Component\Rule\Model\Rule as BaseRule;

    class Rule extends BaseRule
    {
        protected $something;

        public function getSomething()
        {
            return $this->something;
        }

        public function setSomething($something)
        {
            $this->something = $something;
        }
    }

Add class's mapping file:

.. code-block:: yaml

    # Acme\DemoBundle\Resources\config\doctrine\Rule.orm.yml
    Acme\DemoBundle\Entity\Rule:
        type: entity
        table: custom_rule
        fields:
            something:
                type: string

The newly created class needs to be now added to the bundle's configuration:

.. code-block:: yaml

    # app/config/config.yml
    swp_rule:
        persistence:
            orm:
                # ..
                classes:
                    rule:
                        model: Acme\DemoBundle\Entity\Rule

That's it, a newly created class will be used instead.

.. note::

    You could also provide your own implementation for Rule Factory and Rule Repository.
    To find out more about it check :doc:`/bundles/SWPStorageBundle/register_storage`

How rules are processed?
------------------------

You can create Event Subscriber which can listen on whatever event is defined. If the event is dispatched, the subscriber should run Rule Processor which will process all rules.

Example subscriber:

.. code-block:: php

    <?php

    namespace SWP\Bundle\ContentBundle\EventListener;

    use SWP\Bundle\ContentBundle\ArticleEvents;
    use SWP\Bundle\ContentBundle\Event\ArticleEvent;
    use SWP\Component\Rule\Processor\RuleProcessorInterface;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;

    class ProcessArticleRulesSubscriber implements EventSubscriberInterface
    {
        /**
         * @var RuleProcessorInterface
         */
        private $ruleProcessor;

        /**
         * ProcessArticleRulesSubscriber constructor.
         *
         * @param RuleProcessorInterface $ruleProcessor
         */
        public function __construct(RuleProcessorInterface $ruleProcessor)
        {
            $this->ruleProcessor = $ruleProcessor;
        }

        /**
         * {@inheritdoc}
         */
        public static function getSubscribedEvents()
        {
            return [
                ArticleEvents::PRE_CREATE => 'processRules',
            ];
        }

        /**
         * @param ArticleEvent $event
         */
        public function processRules(ArticleEvent $event)
        {
            $this->ruleProcessor->process($event->getArticle());
        }
    }

Enabling separate Monolog channel
---------------------------------

It is possible to enable a separate Monolog channel to which all Rule Bundle related logs will be forwarded. An example log entry might be logged when the rule can not be evaluated properly etc. You could have then a separate log file for (which will log everything related to that bundle) which will be saved under the directory ``app/logs/`` in your application and will be named, for example: ``swp_rule_<env>.log``. By default, a separate channel is not enabled. You can enable it by adding an extra channel in your Monolog settings (in one of your configuration files):

.. code-block:: yaml

    # app/config/config.yml
    monolog:
        handlers:
            swp_rule:
                level:    debug
                type:     stream
                path:     '%kernel.logs_dir%/swp_rule_%kernel.environment%.log'
                channels: swp_rule

For more details see the `Monolog documentation`_.

.. _Monolog documentation: http://symfony.com/doc/current/logging/channels_handlers.html
