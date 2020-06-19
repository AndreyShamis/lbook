<?php
/**
 * User: Andrey Shamis
 * Date: 09.05.20
 * Time: 12:43
 */

namespace App\Form;

use App\Entity\LogBookSetup;
use App\Entity\SuiteExecution;
use App\Entity\SuiteExecutionSearch;
use Doctrine\ORM\EntityManagerInterface;
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
                    'choices' => $this->getUniqPlatforms(),
                    'multiple'=> true,
                    'attr' => [
                        'style' => 'width:400px;display: none;',
                        'class' => 'LogBookSelectablePlatformsType multiselect']
                ]
            )
            ->add('chips', ChoiceType::class, [
                    'required' => false,
                    'choices' => $this->getUniqChips(),
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

    }

    public function test($aa) {
        echo "<pre>";
        print_r($aa);
    }

    /**
     * @return SuiteExecution[]|
     */
    protected function getUniqPlatforms(): array
    {
        return $this->entityManager->getRepository(SuiteExecution::class)->getUniqPlatforms();
    }


    /**
     * @return SuiteExecution[]|
     */
    protected function getUniqChips(): array
    {
        return $this->entityManager->getRepository(SuiteExecution::class)->getUniqChips();
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