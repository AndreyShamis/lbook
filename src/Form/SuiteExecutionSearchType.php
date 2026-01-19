<?php
/**
 * User: Andrey Shamis
 * Date: 09.05.20
 * Time: 12:43
 */

namespace App\Form;

use App\Entity\LogBookSetup;
use App\Entity\StorageString;
use App\Entity\SuiteExecution;
use App\Entity\SuiteExecutionSearch;
use App\Repository\StorageStringRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SuiteExecutionSearchType extends AbstractType
{

    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'attr' => [
                    'style' => 'width:400px;'
                ]])
            ->add('fromDate', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]])
            ->add('toDate', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]])
//            ->add('verdict', LogBookSelectableVerdictType::class, array('required' => false))
            ->add('setup', LogBookSelectableSetupType::class, ['required' => false])
            ->add('limit', QueryLimitType::class)
            ->add('testingLevel', ChoiceType::class, [
                'required' => false,
                'label' => ' ',
                'choices' => [
                    'sanity' => 'sanity',
                    'integration' => 'integration',
                    'nightly' => 'nightly',
                    'weekly' => 'weekly'
                ],
//              'choice_label' => 'TestingLevel',
//              'choice_value' => 'Id',
//              'expanded' => false, //             // false will convert to checkbox
                'multiple'=> true,
                'attr' => [
                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectableTestingLevelType multiselect']
            ])
            ->add('modes', ChoiceType::class, [
                'required' => false,
                'label' => ' ',
                'choices' => [
                    'regular_mode' => 'regular_mode',
                    'package_mode' => 'package_mode'
                ],
//              'choice_label' => 'TestingLevel',
//              'choice_value' => 'Id',
//              'expanded' => false, //             // false will convert to checkbox
                'multiple'=> true,
                'attr' => [
                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectablePackageModeType multiselect']
            ])
            ->add('publish', ChoiceType::class, [
                    'required' => false,
                    'choices' => [
                        'Not Publish' => '0',
                        'Publish' => '1'
                    ],
                    'multiple'=> true,
                    'attr' => [
                        'style' => 'width:400px;display: none;',
                        'class' => 'LogBookSelectablePublishType multiselect']
                ])
            ->add('platforms', ChoiceType::class, [
                    'required' => false,
                    'choice_label' => 'name',
                    'choice_value' => 'name',
                    'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'platforms'),
                    'multiple'=> true,
                    'attr' => [
                        'style' => 'width:400px;display: none;',
                        'class' => 'LogBookSelectablePlatformsType multiselect']
                ]
            )
            ->add('chips', ChoiceType::class, [
                    'required' => false,
                    'choice_label' => 'name',
                    'choice_value' => 'name',
                    'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'chips'),
                    'multiple'=> true,
                    'attr' => [
                        'style' => 'width:400px;display: none;',
                        'class' => 'LogBookSelectableChipsType multiselect']
                ])
            ->add('components', ChoiceType::class, [
                'required' => false,
                'choice_label' => 'name',
                'choice_value' => 'name',
                'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'components'),
                'multiple'=> true,
                'attr' => [
                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectableChipsType multiselect']
            ])
            ->add('jobNames', ChoiceType::class, [
                'required' => false,
                'choice_label' => 'name',
                'choice_value' => 'name',
                'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'jobNames'),
                'multiple'=> true,
                'attr' => [
                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectableChipsType multiselect']
            ]);
            //->add('executionOrder')
//            ->add('cycle')
            //->add('disabled')
        ;
//        echo "<pre>";
//        print_r($this->getUniqPlatforms());
//        exit();
        $nulTransformer = new CallbackTransformer(
            function ($input)
            {
                return null;
            },
            function ($input)
            {
                return null;
            }
        );

//        $builder->get('verdict')->addModelTransformer($nulTransformer);
        $builder->get('setup')->addModelTransformer($nulTransformer);

        $prob = rand(1, 50);
        if ($prob == 2 || $prob == 10) {
            $chips = $this->entityManager->getRepository(SuiteExecution::class)->getUniqChips();
            /** @var StorageStringRepository $storage */
            $storage = $this->entityManager->getRepository(StorageString::class);
            foreach ($chips as $chip) {
                try {
                    if ($chip === null || $chip === ''){
                        continue;
                    }
                    $storage->findOneOrCreate(
                        [
                            'vname' => $chip,
                            'key1' => 'lbk',
                            'key2' => 'suites',
                            'key3' => 'chips',
                        ]
                    );
                } catch (OptimisticLockException $e) {
                } catch (ORMException $e) {
                }
            }

            $platforms = $this->entityManager->getRepository(SuiteExecution::class)->getUniqPlatforms();
            /** @var StorageStringRepository $storage */
            $storage = $this->entityManager->getRepository(StorageString::class);
            foreach ($platforms as $platform) {
                try {
                    if ($platform === null || $platform === ''){
                        continue;
                    }
                    $storage->findOneOrCreate(
                        [
                            'vname' => $platform,
                            'key1' => 'lbk',
                            'key2' => 'suites',
                            'key3' => 'platforms',
                        ]
                    );
                } catch (OptimisticLockException $e) {
                } catch (ORMException $e) {
                }
            }

            $components = $this->entityManager->getRepository(SuiteExecution::class)->getUniqComponents();
            /** @var StorageStringRepository $storage */
            $storage = $this->entityManager->getRepository(StorageString::class);
            foreach ($components as $component) {
                $tmp_arr = explode(',', $component);
                if (count($tmp_arr) > 1) {
                    foreach ($tmp_arr as $tmp_component) {
                        try {
                            if ($tmp_component === null || $tmp_component === ''){
                                continue;
                            }
                            $storage->findOneOrCreate(
                                [
                                    'vname' => $tmp_component,
                                    'key1' => 'lbk',
                                    'key2' => 'suites',
                                    'key3' => 'components',
                                ]
                            );
                        } catch (OptimisticLockException $e) {
                        } catch (ORMException $e) {
                        }
                    }
                } else {
                    try {
                        if ($component === null || $component === ''){
                            continue;
                        }
                        $storage->findOneOrCreate(
                            [
                                'vname' => $component,
                                'key1' => 'lbk',
                                'key2' => 'suites',
                                'key3' => 'components',
                            ]
                        );
                    } catch (OptimisticLockException $e) {
                    } catch (ORMException $e) {
                    }
                }

            }

            $jobNames = $this->entityManager->getRepository(SuiteExecution::class)->getUniqJobNames();
            /** @var StorageStringRepository $storage */
            $storage = $this->entityManager->getRepository(StorageString::class);
            foreach ($jobNames as $jobName) {
                try {
                    if ($jobName === null || $jobName === ''){
                        continue;
                    }
                    $storage->findOneOrCreate(
                        [
                            'vname' => $jobName,
                            'key1' => 'lbk',
                            'key2' => 'suites',
                            'key3' => 'jobNames',
                        ]
                    );
                } catch (OptimisticLockException $e) {
                } catch (ORMException $e) {
                }
            }
        }


    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
//            'compound' => true,
            'data_class' => SuiteExecutionSearch::class,
        ]);
    }
}